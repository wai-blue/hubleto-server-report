<?php

namespace Hubleto\App\External\WaiBlue\ServerReport;

use Hubleto\App\External\WaiBlue\ServerReport\Models\HealthStatus;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Counter;
use Hubleto\App\External\WaiBlue\ServerReport\Services\DefaultData;

class Loader extends \Hubleto\Erp\App
{

  /**
   * Inits the app: adds routes, settings, calendar, boards and workflow. Must stay cheap, it runs on every request.
   *
   * @return void
   *
   */
  public function init(): void
  {
    parent::init();

    $this->router()->crud('server-report', Controllers\Servers::class);
    $this->router()->crud('server-report/providers', Controllers\Providers::class);
    $this->router()->crud('server-report/datacenters', Controllers\Datacenters::class);
    $this->router()->crud('server-report/tags', Controllers\Tags::class);
    $this->router()->crud('server-report/server-has-tags', Controllers\ServerHasTags::class);
    $this->router()->crud('server-report/stack', Controllers\StackItems::class);
    $this->router()->crud('server-report/health', Controllers\HealthStatuses::class);
    $this->router()->crud('server-report/activity-templates', Controllers\ActivityTemplates::class);
    $this->router()->crud('server-report/activities', Controllers\ServerActivities::class);
    $this->router()->crud('server-report/reports', Controllers\ActivityReports::class);

    $this->router()->get([
      '/^server-report\/settings\/?$/' => Controllers\Settings::class,
      '/^server-report\/planner\/?$/' => Controllers\Planner::class,

      '/^server-report\/board\/health-overview\/?$/' => Controllers\Boards\HealthOverview::class,
      '/^server-report\/board\/health-detail\/?$/' => Controllers\Boards\HealthDetail::class,

      '/^server-report\/api\/get-server-overview\/?$/' => Controllers\Api\GetServerOverview::class,
      '/^server-report\/api\/generate-report\/?$/' => Controllers\Api\GenerateReport::class,
      '/^server-report\/api\/get-planner-data\/?$/' => Controllers\Api\GetPlannerData::class,
      '/^server-report\/api\/plan-activities\/?$/' => Controllers\Api\PlanActivities::class,
      '/^server-report\/api\/log-activity\/?$/' => Controllers\Api\LogActivity::class,
      '/^server-report\/api\/create-report-from-activity\/?$/' => Controllers\Api\CreateReportFromActivity::class,
    ]);

    /** @var \Hubleto\App\Community\Settings\Loader $settingsApp */
    $settingsApp = $this->appManager()->getApp(\Hubleto\App\Community\Settings\Loader::class);
    $settingsApp->addSetting($this, [
      'title' => $this->translate('Servers'),
      'icon' => 'fas fa-server',
      'url' => 'server-report/settings',
    ]);
    $settingsApp->addSetting($this, [
      'title' => $this->translate('Server tags'),
      'icon' => 'fas fa-tags',
      'url' => 'server-report/tags',
    ]);

    /** @var \Hubleto\App\Community\Workflow\Manager $workflowManager */
    $workflowManager = $this->getService(\Hubleto\App\Community\Workflow\Manager::class);
    $workflowManager->addWorkflowGroup($this, DefaultData::WORKFLOW_GROUP, Workflow::class);

    /** @var \Hubleto\App\Community\Calendar\Manager $calendarManager */
    $calendarManager = $this->getService(\Hubleto\App\Community\Calendar\Manager::class);
    $calendarManager->addCalendar($this, 'server-report', Calendar::class);

    /** @var \Hubleto\App\Community\Dashboards\Manager $dashboardManager */
    $dashboardManager = $this->getService(\Hubleto\App\Community\Dashboards\Manager::class);
    $dashboardManager->addBoard($this, $this->translate('Servers: health overview'), 'server-report/board/health-overview', Controllers\Boards\HealthOverview::class);
    $dashboardManager->addBoard($this, $this->translate('Servers: detailed health and activities'), 'server-report/board/health-detail', Controllers\Boards\HealthDetail::class);
  }

  /**
   * Installs the app: creates tables and seeds default data (workflow, report template, typical activities).
   * The seeding is idempotent and runs in both rounds, because `php hubleto app install` runs only round 1.
   *
   * @param int $round
   *
   * @return void
   *
   */
  public function installApp(int $round): void
  {
    if ($round == 1) {
      $modelClasses = [
        Models\Datacenter::class,
        Models\Provider::class,
        Models\Tag::class,
        Models\Server::class,
        Models\ServerHasTag::class,
        Models\StackItem::class,
        Models\HealthStatus::class,
        Models\ActivityTemplate::class,
        Models\ServerActivity::class,
        Models\ActivityReport::class,
      ];

      foreach ($modelClasses as $modelClass) {
        $this->getModel($modelClass)->upgradeSchema();
      }
    }

    if ($round == 1 || $round == 2) {
      /** @var DefaultData $defaultData */
      $defaultData = $this->getService(DefaultData::class);
      $defaultData->ensure();
    }
  }

  /**
   * Second sidebar of the app.
   *
   * @return string
   *
   */
  public function renderSecondSidebar(): string
  {
    /** @var Counter $counter */
    $counter = $this->getService(Counter::class);

    return '
      ' . $this->secondSidebarTitle() . '
      <div class="app-sidebar-buttons">
        ' . $this->secondSidebarButton('server-report/board/health-overview', 'fas fa-heart-pulse', 'Dashboard') . '
        ' . $this->secondSidebarButton('server-report/board/health-detail', 'fas fa-gauge-high', 'Detailed dashboard') . '
        ' . $this->secondSidebarButton('server-report', 'fas fa-server', 'Servers', $counter->criticalServers()) . '
        ' . $this->secondSidebarButton('server-report/planner', 'fas fa-calendar-check', 'Planner') . '
        ' . $this->secondSidebarButton('server-report/activities', 'fas fa-list-check', 'Planned activities', $counter->overdueActivities()) . '
        ' . $this->secondSidebarButton('server-report/reports', 'fas fa-file-lines', 'Activity reports') . '
        ' . $this->secondSidebarButton('calendar?show=server-report', 'fas fa-calendar-days', 'Calendar') . '
        ' . $this->secondSidebarButton('server-report/health', 'fas fa-notes-medical', 'Health history') . '
        ' . $this->secondSidebarButton('server-report/stack', 'fas fa-layer-group', 'Application stack') . '
        ' . $this->secondSidebarButton('server-report/providers', 'fas fa-truck-field', 'Hardware providers') . '
        ' . $this->secondSidebarButton('server-report/datacenters', 'fas fa-building', 'Datacenters') . '
        ' . $this->secondSidebarButton('server-report/activity-templates', 'fas fa-clipboard-list', 'Typical activities') . '
        ' . $this->secondSidebarButton('server-report/settings', 'fas fa-cog', 'Settings') . '
      </div>
    ';
  }

  /**
   * Alerts shown on the desktop.
   *
   * @return string
   *
   */
  public function renderAlerts(): string
  {
    /** @var Counter $counter */
    $counter = $this->getService(Counter::class);

    $critical = $counter->criticalServers();
    $stale = $counter->staleServers();
    $overdue = $counter->overdueActivities();

    $criticalUrl = $this->env()->projectUrl . '/server-report?filters%5BfServerHealth%5D%5B0%5D=' . HealthStatus::OVERALL_CRITICAL
      . '&filters%5BfServerHealth%5D%5B1%5D=' . HealthStatus::OVERALL_DOWN . '&filters%5BfServerClosed%5D%5B0%5D=0';

    return
      ($critical > 0 ? '
        <a href="' . $criticalUrl . '">'
          . $critical . ' ' . $this->translate('servers are in critical state or down')
        . '</a><br/>
      ' : '')
      . ($stale > 0 ? '
        <a href="' . $this->env()->projectUrl . '/server-report/board/health-detail">'
          . $stale . ' ' . $this->translate('servers have no recent health check')
        . '</a><br/>
      ' : '')
      . ($overdue > 0 ? '
        <a href="' . $this->env()->projectUrl . '/server-report/activities?filters%5BfActivityWhen%5D%5B0%5D=1">'
          . $overdue . ' ' . $this->translate('planned server activities are overdue')
        . '</a><br/>
      ' : '')
    ;
  }

  /**
   * Number shown in the badge of the app in the sidebar.
   *
   * @return int
   *
   */
  public function getSidebarBadgeNumber(): int
  {
    /** @var Counter $counter */
    $counter = $this->getService(Counter::class);
    return $counter->criticalServers() + $counter->overdueActivities();
  }

  /**
   * Global search.
   *
   * @param array $expressions
   *
   * @return array
   *
   */
  public function search(array $expressions): array
  {
    /** @var Models\Server $mServer */
    $mServer = $this->getModel(Models\Server::class);
    $query = $mServer->record->prepareReadQuery();

    $query = $query->where(function ($q) use ($mServer, $expressions) {
      foreach ($expressions as $e) {
        $q->orWhere($mServer->table . '.name', 'like', '%' . $e . '%');
        $q->orWhere($mServer->table . '.description', 'like', '%' . $e . '%');
      }
    });

    $items = $query->limit(20)->get()?->toArray() ?? [];

    $results = [];
    foreach ($items as $item) {
      $results[] = [
        'id' => $item['id'],
        'label' => $item['name'],
        'url' => 'server-report/' . $item['id'],
        'description' => $this->translate('Server'),
      ];
    }

    return $results;
  }

  /**
   * Creates demo data. Idempotent: records are found by their names first.
   *
   * @return void
   *
   */
  public function generateDemoData(): void
  {
    /** @var Services\DemoData $demo */
    $demo = $this->getService(Services\DemoData::class);
    $demo->generate();
  }

}
