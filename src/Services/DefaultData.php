<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\Community\Documents\Models\Template;
use Hubleto\App\Community\Settings\Models\ActivityType;
use Hubleto\App\Community\Workflow\Models\Workflow as WorkflowModel;
use Hubleto\App\Community\Workflow\Models\WorkflowStep;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityTemplate;

/**
 * Default data of the app: workflow of a server, template of the PDF server report, typical activities.
 * Every method is idempotent. They are called from installApp() and, as a safety net, when a server is created.
 */
class DefaultData extends \Hubleto\Erp\Core
{
  public const WORKFLOW_GROUP = 'server-report';
  public const TEMPLATE_USED_FOR = 'servers-server-report';

  public const TAG_PLANNED = 'servers-planned';
  public const TAG_PROVISIONING = 'servers-provisioning';
  public const TAG_PRODUCTION = 'servers-production';
  public const TAG_MAINTENANCE = 'servers-maintenance';
  public const TAG_DECOMMISSIONING = 'servers-decommissioning';
  public const TAG_DECOMMISSIONED = 'servers-decommissioned';

  /**
   * Makes sure that all default data exist. A failure of one part (e.g. a missing table of an app that is not installed yet)
   * must not break the installation or saving of a server, so parts are protected separately.
   *
   * @return void
   *
   */
  public function ensure(): void
  {
    foreach (['ensureWorkflow', 'ensureReportTemplate', 'ensureActivityTemplates'] as $part) {
      try {
        $this->{$part}();
      } catch (\Throwable $e) {
        // intentionally ignored, the part is retried the next time
      }
    }
  }

  /**
   * Creates the workflow of servers and its typical steps.
   *
   * @return void
   *
   */
  public function ensureWorkflow(): void
  {
    /** @var WorkflowModel $mWorkflow */
    $mWorkflow = $this->getModel(WorkflowModel::class);
    /** @var WorkflowStep $mStep */
    $mStep = $this->getModel(WorkflowStep::class);

    if ($mWorkflow->record->where('group', self::WORKFLOW_GROUP)->count() > 0) return;

    $workflow = $mWorkflow->record->recordCreate([
      'name' => $this->translate('Server lifecycle'),
      'order' => 100,
      'group' => self::WORKFLOW_GROUP,
      'description' => $this->translate('Typical lifecycle of a managed server'),
      'show_in_kanban' => 1,
    ]);

    $steps = [
      [$this->translate('Planned'), '#94a3b8', self::TAG_PLANNED, 0],
      [$this->translate('Provisioning'), '#3b82f6', self::TAG_PROVISIONING, 25],
      [$this->translate('In production'), '#22c55e', self::TAG_PRODUCTION, 100],
      [$this->translate('Maintenance'), '#f59e0b', self::TAG_MAINTENANCE, 100],
      [$this->translate('Decommissioning'), '#f97316', self::TAG_DECOMMISSIONING, 0],
      [$this->translate('Decommissioned'), '#6b7280', self::TAG_DECOMMISSIONED, 0],
    ];

    foreach ($steps as $i => [$name, $color, $tag, $probability]) {
      $mStep->record->recordCreate([
        'id_workflow' => (int) $workflow['id'],
        'name' => $name,
        'order' => ($i + 1) * 10,
        'color' => $color,
        'tag' => $tag,
        'probability' => $probability,
      ]);
    }
  }

  /**
   * Creates the default template of the PDF server report.
   *
   * @return void
   *
   */
  public function ensureReportTemplate(): void
  {
    if ($this->getReportTemplateId() > 0) return;

    /** @var Template $mTemplate */
    $mTemplate = $this->getModel(Template::class);

    $file = __DIR__ . '/../DefaultTemplates/ServerReport.twig';
    $content = is_file($file) ? (string) file_get_contents($file) : '';

    $mTemplate->record->recordCreate([
      'name' => $this->translate('Server report'),
      'used_for' => self::TEMPLATE_USED_FOR,
      'content' => $content,
      'notes' => $this->translate('Default template of the PDF server report (app Servers).'),
    ]);
  }

  /**
   * Id of the default template of the server report, 0 if it does not exist.
   *
   * @return int
   *
   */
  public function getReportTemplateId(): int
  {
    /** @var Template $mTemplate */
    $mTemplate = $this->getModel(Template::class);
    $template = $mTemplate->record->where('used_for', self::TEMPLATE_USED_FOR)->orderBy('id')->first();
    return $template ? (int) $template->id : 0;
  }

  /**
   * Creates activity types (in the Settings app) and typical activities of system administrators.
   *
   * @return void
   *
   */
  public function ensureActivityTemplates(): void
  {
    /** @var ActivityTemplate $mTemplate */
    $mTemplate = $this->getModel(ActivityTemplate::class);
    if ($mTemplate->record->count() > 0) return;

    $types = [
      'maintenance' => [$this->translate('Server maintenance'), '#0ea5e9'],
      'health' => [$this->translate('Server health check'), '#16a34a'],
      'backup' => [$this->translate('Server backup and recovery'), '#7c3aed'],
      'security' => [$this->translate('Server security'), '#dc2626'],
      'hardware' => [$this->translate('Server hardware'), '#f59e0b'],
    ];

    /** @var ActivityType $mType */
    $mType = $this->getModel(ActivityType::class);
    $idTypes = [];
    foreach ($types as $key => [$name, $color]) {
      $type = $mType->record->where('name', $name)->first();
      if (!$type) {
        $type = (object) $mType->record->recordCreate([
          'name' => $name, 'color' => $color, 'calendar_visibility' => 1, 'icon' => 'fas fa-server',
        ]);
      }
      $idTypes[$key] = (int) $type->id;
    }

    $defaults = [
      ['Monthly system updates', 'maintenance', ActivityTemplate::PERIOD_MONTH, 1, 12, 120, '22:00:00',
        "Check pending updates.\nTake a snapshot or backup.\nInstall OS and package updates.\nReboot if required.\nVerify that all services are running.\nRecord new versions in the application stack."],
      ['Weekly manual health check', 'health', ActivityTemplate::PERIOD_WEEK, 1, 52, 30, '09:00:00',
        "Check CPU, memory and disk usage.\nCheck failed services and the system log.\nCheck backup status.\nRecord the result in the health history."],
      ['Quarterly backup restore test', 'backup', ActivityTemplate::PERIOD_MONTH, 3, 4, 90, '10:00:00',
        "Restore the latest backup to a test location.\nVerify integrity of restored data.\nRecord the duration and the result."],
      ['Monthly security patch review', 'security', ActivityTemplate::PERIOD_MONTH, 1, 12, 60, '14:00:00',
        "Review security advisories for the application stack.\nPlan and apply critical patches.\nReview user accounts and SSH keys."],
      ['Quarterly certificate and licence check', 'security', ActivityTemplate::PERIOD_MONTH, 3, 4, 30, '11:00:00',
        "Check expiry of TLS certificates and software licences.\nRenew those expiring within 60 days."],
      ['Yearly hardware inspection', 'hardware', ActivityTemplate::PERIOD_YEAR, 1, 3, 120, '09:00:00',
        "Check hardware alerts, fans, disks and power supplies.\nCheck warranty and support contract.\nPlan hardware replacement if needed."],
    ];

    foreach ($defaults as [$name, $typeKey, $period, $every, $count, $duration, $time, $description]) {
      $mTemplate->record->recordCreate([
        'name' => $this->translate($name),
        'id_activity_type' => $idTypes[$typeKey],
        'description' => $this->translate($description),
        'period' => $period,
        'period_every' => $every,
        'period_count' => $count,
        'duration_minutes' => $duration,
        'default_time' => $time,
        'is_active' => 1,
      ]);
    }
  }
}
