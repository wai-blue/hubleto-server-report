<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Virtual;

class ServerActivity extends \Hubleto\App\Community\Calendar\Models\Activity
{
  public string $table = 'servers_activities';
  public string $recordManagerClass = RecordManagers\ServerActivity::class;
  public ?string $lookupUrlDetail = 'server-report/activities/{%ID%}';

  public array $relations = [
    'SERVER' => [ self::BELONGS_TO, Server::class, 'id_server', 'id' ],
    'TEMPLATE' => [ self::BELONGS_TO, ActivityTemplate::class, 'id_template', 'id' ],
    'REPORTS' => [ self::HAS_MANY, ActivityReport::class, 'id_activity', 'id' ],
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
      'id_template' => (new Lookup($this, $this->translate('Typical activity'), ActivityTemplate::class))->setFkOnDelete('SET NULL'),
      'virt_reports' => (new Virtual($this, $this->translate('Reports')))->setDefaultVisible()->setProperty('sql', "
          SELECT COUNT(*) FROM `servers_activity_reports` WHERE `servers_activity_reports`.`id_activity` = `servers_activities`.`id`
        "),
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

    $description->ui['addButtonText'] = $this->translate('Plan activity');

    $description->ui['title'] = '';
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    $description->addFilter('fActivityWhen', [
      'title' => $this->translate('When'),
      'type' => 'multipleSelectButtons',
      'options' => [ 1 => $this->translate('Overdue'), 2 => $this->translate('Today'), 3 => $this->translate('Upcoming'), 4 => $this->translate('Done') ],
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
   * When an activity is completed, a draft of its report is created (if enabled in the settings).
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

    $wasCompleted = (int) ($originalRecord['completed'] ?? 0) === 1;
    $isCompleted = (int) ($savedRecord['completed'] ?? 0) === 1;

    if (!$wasCompleted && $isCompleted) {
      /** @var Settings $settings */
      $settings = $this->getService(Settings::class);
      if ($settings->getBool('autoDraftReport')) {
        /** @var ActivityReport $mReport */
        $mReport = $this->getModel(ActivityReport::class);
        $exists = $mReport->record->where($mReport->table . '.id_activity', (int) $savedRecord['id'])->count() > 0;
        if (!$exists) {
          $mReport->record->recordCreate(['id_activity' => (int) $savedRecord['id']]);
        }
      }
    }

    return $savedRecord;
  }

}
