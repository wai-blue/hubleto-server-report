<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Health;
use Hubleto\Framework\Db\Column\DateTime;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Json;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Exceptions\RecordSaveException;

class HealthStatus extends \Hubleto\Erp\Model
{
  public const TABLE = 'servers_health_statuses';

  public const OVERALL_UNKNOWN = 0;
  public const OVERALL_OK = 1;
  public const OVERALL_WARNING = 2;
  public const OVERALL_CRITICAL = 3;
  public const OVERALL_DOWN = 4;

  public string $table = self::TABLE;
  public string $recordManagerClass = RecordManagers\HealthStatus::class;
  public ?string $lookupSqlValue = 'concat({%TABLE%}.datetime_checked, " #", {%TABLE%}.id)';
  public ?string $lookupUrlAdd = 'server-report/health/add';
  public ?string $lookupUrlDetail = 'server-report/health/{%ID%}';

  public array $relations = [
    'SERVER' => [ self::BELONGS_TO, Server::class, 'id_server', 'id' ],
    'CHECKED_BY' => [ self::BELONGS_TO, User::class, 'id_checked_by', 'id' ],
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
      'datetime_checked' => (new DateTime($this, $this->translate('Checked at')))->setRequired()->setDefaultVisible(),
      'overall' => (new Integer($this, $this->translate('Overall status')))->setRequired()->setDefaultVisible()->setDefaultValue(self::OVERALL_OK)->setEnumValues([self::OVERALL_UNKNOWN => $this->translate('Unknown'), self::OVERALL_OK => $this->translate('OK'), self::OVERALL_WARNING => $this->translate('Warning'), self::OVERALL_CRITICAL => $this->translate('Critical'), self::OVERALL_DOWN => $this->translate('Down')])->setEnumCssClasses([self::OVERALL_UNKNOWN => 'bg-gray-300', self::OVERALL_OK => 'bg-green-300', self::OVERALL_WARNING => 'bg-yellow-300', self::OVERALL_CRITICAL => 'bg-orange-300', self::OVERALL_DOWN => 'bg-red-300']),
      'status' => (new Json($this, $this->translate('Status details')))->setReactComponent('InputJsonKeyValue')->setHint($this->translate('Key-value pairs, for example cpu_percent, memory_percent, disk_percent, load_avg, uptime_days.')),
      'note' => (new Text($this, $this->translate('Note'))),
      'id_checked_by' => (new Lookup($this, $this->translate('Checked by'), User::class))->setFkOnDelete('SET NULL')->setReactComponent('InputUserSelect')->setDefaultVisible(),
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

    $description->ui['addButtonText'] = $this->translate('Record health check');

    $description->ui['title'] = '';
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    /** @var Health $health */
    $health = $this->getService(Health::class);
    $description->addFilter('fHealthOverall', [
      'title' => $this->translate('Overall status'),
      'type' => 'multipleSelectButtons',
      'options' => $health->labels(),
      'colors' => $health->colors(),
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
   * Latest overall status of a server as an SQL sub-select. Used by virtual columns, filters and counters.
   *
   * @param string $serverTable Name of the table of servers in the outer query
   *
   * @return string
   *
   */
  public static function latestOverallSql(string $serverTable): string
  {
    return 'SELECT h.overall FROM `' . self::TABLE . '` h WHERE h.id_server = `' . $serverTable . '`.`id` ORDER BY h.datetime_checked DESC, h.id DESC LIMIT 1';
  }

  /**
   * Time of the latest health check of a server as an SQL sub-select.
   *
   * @param string $serverTable Name of the table of servers in the outer query
   *
   * @return string
   *
   */
  public static function latestCheckedSql(string $serverTable): string
  {
    return 'SELECT h.datetime_checked FROM `' . self::TABLE . '` h WHERE h.id_server = `' . $serverTable . '`.`id` ORDER BY h.datetime_checked DESC, h.id DESC LIMIT 1';
  }

  /**
   * Status details must be a JSON object.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeValidate(array $record): array
  {
    $record = parent::onBeforeValidate($record);

    $status = $record['status'] ?? null;
    if (is_string($status) && trim($status) !== '') {
      $decoded = json_decode($status, true);
      if (!is_array($decoded)) {
        throw new RecordSaveException($this->translate('Status details must be a valid JSON object.'), ['status']);
      }
    }

    return $record;
  }

  /**
   * Fills the time and the author of the check.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);
    if (trim((string) ($record['datetime_checked'] ?? '')) === '') $record['datetime_checked'] = date('Y-m-d H:i:s');
    if ((int) ($record['id_checked_by'] ?? 0) <= 0) $record['id_checked_by'] = $this->authProvider()->getUserId();
    return $record;
  }

}
