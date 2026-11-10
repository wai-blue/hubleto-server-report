<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;
use Hubleto\Framework\Db\Column\Date;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Time;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Exceptions\RecordSaveException;

class ActivityReport extends \Hubleto\Erp\Model
{
  public string $table = 'servers_activity_reports';
  public string $recordManagerClass = RecordManagers\ActivityReport::class;
  public ?string $lookupSqlValue = 'concat(ifnull({%TABLE%}.activity_date, ""), " ", ifnull({%TABLE%}.activity_subject, ""))';
  public ?string $lookupUrlAdd = 'server-report/reports/add';
  public ?string $lookupUrlDetail = 'server-report/reports/{%ID%}';

  public array $relations = [
    'ACTIVITY' => [ self::BELONGS_TO, ServerActivity::class, 'id_activity', 'id' ],
    'SERVER' => [ self::BELONGS_TO, Server::class, 'id_server', 'id' ],
    'WORKER' => [ self::BELONGS_TO, User::class, 'id_worker', 'id' ],
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
      'id_activity' => (new Lookup($this, $this->translate('Activity'), ServerActivity::class))->setFkOnDelete('SET NULL')->setDefaultVisible(),
      'activity_subject' => (new Varchar($this, $this->translate('Activity (copy)')))->setDefaultVisible()->setReadonly(),
      'activity_description' => (new Text($this, $this->translate('Description of the activity (copy)')))->setReadonly(),
      'activity_date' => (new Date($this, $this->translate('Date (copy)')))->setDefaultVisible()->setReadonly(),
      'activity_time' => (new Time($this, $this->translate('Time (copy)')))->setReadonly(),
      'id_server' => (new Lookup($this, $this->translate('Server'), Server::class))->setFkOnDelete('SET NULL')->setDefaultVisible()->setReadonly(),
      'server_name' => (new Varchar($this, $this->translate('Server name (copy)')))->setReadonly(),
      'id_worker' => (new Lookup($this, $this->translate('Worked by'), User::class))->setFkOnDelete('SET NULL')->setReactComponent('InputUserSelect')->setDefaultVisible()->setDefaultValue($this->authProvider()->getUserId()),
      'worker_name' => (new Varchar($this, $this->translate('Worker name (copy)')))->setDefaultVisible()->setReadonly(),
      'steps_performed' => (new Text($this, $this->translate('Steps performed'))),
      'result' => (new Text($this, $this->translate('Result'))),
      'followup' => (new Text($this, $this->translate('Required follow-up activities'))),
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

    $description->ui['addButtonText'] = $this->translate('Add report');

    $description->ui['title'] = '';
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    $description->addFilter('fReportFollowup', [
      'title' => $this->translate('Follow-up'),
      'type' => 'multipleSelectButtons',
      'options' => [ 1 => $this->translate('With follow-up'), 2 => $this->translate('Without follow-up') ],
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

    $idFromUrl = $this->router()->urlParamAsInteger('idActivity');
    if ($idFromUrl > 0) $description->defaultValues['id_activity'] = $idFromUrl;

    $idFromUrl = $this->router()->urlParamAsInteger('idServer');
    if ($idFromUrl > 0) $description->defaultValues['id_server'] = $idFromUrl;

    return $description;
  }

  /**
   * A new report must be linked to an activity.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeValidate(array $record): array
  {
    $record = parent::onBeforeValidate($record);

    $isNew = (int) ($record['id'] ?? 0) <= 0;
    if ($isNew && (int) ($record['id_activity'] ?? 0) <= 0) {
      throw new RecordSaveException($this->translate('Select the activity this report belongs to.'), ['id_activity']);
    }

    return $record;
  }

  /**
   * Copies data of the activity into the report (the report keeps them even if the activity is changed or deleted).
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeCreate(array $record): array
  {
    $record = parent::onBeforeCreate($record);

    /** @var ServerActivity $mActivity */
    $mActivity = $this->getModel(ServerActivity::class);
    $activity = $mActivity->record->where($mActivity->table . '.id', (int) ($record['id_activity'] ?? 0))->first();

    if ($activity) {
      $record['activity_subject'] = (string) $activity->subject;
      $record['activity_description'] = (string) $activity->description;
      $record['activity_date'] = $activity->date_start;
      $record['activity_time'] = $activity->time_start;
      $record['id_server'] = (int) $activity->id_server;
      if ((int) ($record['id_worker'] ?? 0) <= 0) {
        $record['id_worker'] = (int) ($activity->id_owner ?: $this->authProvider()->getUserId());
      }
    }

    $record = $this->copyNames($record);

    return $record;
  }

  /**
   * Keeps the copied name of the worker in sync with the selected worker.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeUpdate(array $record): array
  {
    $record = parent::onBeforeUpdate($record);
    return $this->copyNames($record);
  }

  /**
   * Fills the copied names of the server and of the worker.
   *
   * @param array $record
   *
   * @return array
   *
   */
  private function copyNames(array $record): array
  {
    if ((int) ($record['id_server'] ?? 0) > 0) {
      /** @var Server $mServer */
      $mServer = $this->getModel(Server::class);
      $server = $mServer->record->where($mServer->table . '.id', (int) $record['id_server'])->first();
      if ($server) $record['server_name'] = (string) $server->name;
    }

    if ((int) ($record['id_worker'] ?? 0) > 0) {
      /** @var \Hubleto\App\Community\Auth\Models\User $mUser */
      $mUser = $this->getModel(\Hubleto\App\Community\Auth\Models\User::class);
      $user = $mUser->record->where('id', (int) $record['id_worker'])->first();
      if ($user) {
        $name = trim((string) $user->first_name . ' ' . (string) $user->last_name);
        if ($name === '') $name = (string) ($user->nick ?: $user->email);
        $record['worker_name'] = $name;
      }
    }

    return $record;
  }

}
