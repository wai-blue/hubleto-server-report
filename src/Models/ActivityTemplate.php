<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models;

use Hubleto\App\Community\Settings\Models\ActivityType;
use Hubleto\Framework\Db\Column\Boolean;
use Hubleto\Framework\Db\Column\Integer;
use Hubleto\Framework\Db\Column\Lookup;
use Hubleto\Framework\Db\Column\Text;
use Hubleto\Framework\Db\Column\Time;
use Hubleto\Framework\Db\Column\Varchar;
use Hubleto\Framework\Exceptions\RecordSaveException;

class ActivityTemplate extends \Hubleto\Erp\Model
{
  public const PERIOD_NONE = 0;
  public const PERIOD_DAY = 1;
  public const PERIOD_WEEK = 2;
  public const PERIOD_MONTH = 3;
  public const PERIOD_YEAR = 4;

  public string $table = 'servers_activity_templates';
  public string $recordManagerClass = RecordManagers\ActivityTemplate::class;
  public ?string $lookupSqlValue = '{%TABLE%}.name';
  public ?string $lookupUrlAdd = 'server-report/activity-templates/add';
  public ?string $lookupUrlDetail = 'server-report/activity-templates/{%ID%}';

  public array $relations = [
    'ACTIVITY_TYPE' => [ self::BELONGS_TO, ActivityType::class, 'id_activity_type', 'id' ],
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
      'name' => (new Varchar($this, $this->translate('Name')))->setRequired()->setDefaultVisible(),
      'id_activity_type' => (new Lookup($this, $this->translate('Activity type'), ActivityType::class))->setFkOnDelete('SET NULL')->setDefaultVisible(),
      'description' => (new Text($this, $this->translate('Description and checklist'))),
      'period' => (new Integer($this, $this->translate('Repeats')))->setDefaultVisible()->setDefaultValue(self::PERIOD_MONTH)->setEnumValues([self::PERIOD_NONE => $this->translate('Once'), self::PERIOD_DAY => $this->translate('Daily'), self::PERIOD_WEEK => $this->translate('Weekly'), self::PERIOD_MONTH => $this->translate('Monthly'), self::PERIOD_YEAR => $this->translate('Yearly')]),
      'period_every' => (new Integer($this, $this->translate('Every (number of periods)')))->setDefaultValue(1),
      'period_count' => (new Integer($this, $this->translate('Number of occurrences')))->setDefaultValue(12),
      'duration_minutes' => (new Integer($this, $this->translate('Duration (minutes)')))->setDefaultVisible()->setDefaultValue(60),
      'default_time' => (new Time($this, $this->translate('Default start time'))),
      'is_active' => (new Boolean($this, $this->translate('Active')))->setDefaultVisible()->setDefaultValue(true),
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

    $description->ui['addButtonText'] = $this->translate('Add typical activity');

    $description->ui['title'] = '';
    $description->show(['header', 'fulltextSearch', 'columnSearch', 'moreActionsButton']);

    return $description;
  }


  /**
   * Checks the recurrence settings.
   *
   * @param array $record
   *
   * @return array
   *
   */
  public function onBeforeValidate(array $record): array
  {
    $record = parent::onBeforeValidate($record);

    if ((int) ($record['period'] ?? self::PERIOD_NONE) !== self::PERIOD_NONE) {
      $every = (int) ($record['period_every'] ?? 1);
      $count = (int) ($record['period_count'] ?? 1);
      if ($every < 1 || $every > 60) {
        throw new RecordSaveException($this->translate('"Every" must be between 1 and 60.'), ['period_every']);
      }
      if ($count < 1 || $count > 366) {
        throw new RecordSaveException($this->translate('Number of occurrences must be between 1 and 366.'), ['period_count']);
      }
    }

    if ((int) ($record['duration_minutes'] ?? 0) < 0) {
      throw new RecordSaveException($this->translate('Duration must not be negative.'), ['duration_minutes']);
    }

    return $record;
  }

}
