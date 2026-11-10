<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\External\WaiBlue\ServerReport\Services\SecretGuard;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;
use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Exceptions\RecordSaveException;

class StackItem extends \Hubleto\Erp\Model
{
  public string $table = 'servers_stack_items';
  public string $recordManagerClass = RecordManagers\StackItem::class;
  public ?string $lookupSqlValue = 'concat({%TABLE%}.name, " ", ifnull({%TABLE%}.version, ""))';
  public ?string $lookupUrlAdd = 'server-report/stack/add';
  public ?string $lookupUrlDetail = 'server-report/stack/{%ID%}';

  public array $relations = [
    'SERVER' => [ self::BELONGS_TO, Server::class, 'id_server', 'id' ],
    'RECORDED_BY' => [ self::BELONGS_TO, User::class, 'id_recorded_by', 'id' ],
  ];

  /**
   * Describes columns of the model.
   *
   * @return array
   *
   */
  public function describeColumns(): array
  {
    return array_merge(parent::describeColumns(), [
      'id_server' => (new Lookup($this, $this->translate('Server'), Server::class))->setFkOnDelete('CASCADE')->setRequired()->setDefaultVisible(),
      'name' => (new Varchar($this, $this->translate('Application')))->setRequired()->setDefaultVisible(),
      'version' => (new Varchar($this, $this->translate('Version')))->setDefaultVisible(),
      'configuration' => (new Json($this, $this->translate('Configuration')))->setReactComponent('InputJsonKeyValue'),
      'datetime_recorded' => (new DateTime($this, $this->translate('Recorded at')))->setDefaultVisible(),
      'id_recorded_by' => (new Lookup($this, $this->translate('Recorded by'), User::class))->setFkOnDelete('SET NULL')->setReactComponent('InputUserSelect')->setDefaultVisible(),
      'is_current' => (new Boolean($this, $this->translate('Current')))->setDefaultVisible()->setDefaultValue(true),
      'note' => (new Text($this, $this->translate('Note'))),
    ]);
  }

  /**
   * Describes the table (list) of records.
   *
   * @return \Hubleto\Framework\Description\Table
   *
   */
  public function describeTable(): \Hubleto\Framework\Description\Table
  {
    $description = parent::describeTable();

    $description->ui['addButtonText'] = $this->translate('Add application');

    $description->ui['title'] = '';
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    $description->addFilter('fStackCurrent', [
      'title' => $this->translate('Show'),
      'type' => 'multipleSelectButtons',
      'options' => [ 1 => $this->translate('Current'), 2 => $this->translate('History') ],
    ]);

    return $description;
  }

  /**
   * Describes the form of a record.
   *
   * @return \Hubleto\Framework\Description\Form
   *
   */
  public function describeForm(): \Hubleto\Framework\Description\Form
  {
    $description = parent::describeForm();

    $idFromUrl = $this->router()->urlParamAsInteger('idServer');
    if ($idFromUrl > 0) $description->defaultValues['id_server'] = $idFromUrl;

    return $description;
  }

  /**
   * Rejects configuration that looks like it contains secrets.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeValidate(array $record): array
  {
    $record = parent::onBeforeValidate($record);

    /** @var Settings $settings */
    $settings = $this->getService(Settings::class);
    if ($settings->getBool('rejectSecrets')) {
      /** @var SecretGuard $guard */
      $guard = $this->getService(SecretGuard::class);
      $configuration = $record['configuration'] ?? null;
      if (is_string($configuration)) $configuration = json_decode($configuration, true);
      if (is_array($configuration) && $guard->arrayContainsSecret($configuration)) {
        throw new RecordSaveException(
          $this->translate('Configuration seems to contain a password or another secret. Do not store secrets here.'),
          ['configuration']
        );
      }
    }

    return $record;
  }

  /**
   * Fills the time and the author of the record.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    if (trim((string) ($record['datetime_recorded'] ?? '')) === '') $record['datetime_recorded'] = date('Y-m-d H:i:s');
    if ((int) ($record['id_recorded_by'] ?? 0) <= 0) $record['id_recorded_by'] = $this->authProvider()->getUserId();
    if (!isset($record['is_current'])) $record['is_current'] = 1;
    return $record;
  }

  /**
   * A new current record of an application makes the older ones history.
   *
   * @param array $savedRecord
   *
   * @return array
   *
   */
  public function onAfterCreate(array $savedRecord): array
  {
    $savedRecord = parent::onAfterCreate($savedRecord);
    $this->markOthersAsHistory($savedRecord);
    return $savedRecord;
  }

  /**
   * Setting an older record as current makes the others history.
   *
   * @param array $originalRecord
   * @param array $savedRecord
   *
   * @return array
   *
   */
  public function onAfterUpdate(array $originalRecord, array $savedRecord): array
  {
    $savedRecord = parent::onAfterUpdate($originalRecord, $savedRecord);
    $this->markOthersAsHistory($savedRecord);
    return $savedRecord;
  }

  /**
   * Marks other records of the same application on the same server as history.
   *
   * @param array $record
   *
   * @return void
   *
   */
  private function markOthersAsHistory(array $record): void
  {
    if ((int) ($record['is_current'] ?? 0) !== 1) return;
    if ((int) ($record['id_server'] ?? 0) <= 0 || trim((string) ($record['name'] ?? '')) === '') return;

    $this->record
      ->where($this->table . '.id_server', (int) $record['id_server'])
      ->where($this->table . '.id', '<>', (int) $record['id'])
      ->whereRaw('lower(' . $this->table . '.name) = ?', [mb_strtolower(trim((string) $record['name']))])
      ->update(['is_current' => 0])
    ;
  }

}
