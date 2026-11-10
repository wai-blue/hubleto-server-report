<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Api;

use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityReport;
use Hubleto\App\External\WaiBlue\ServerReport\Models\HealthStatus;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;
use Hubleto\App\External\WaiBlue\ServerReport\Models\StackItem;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Health;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Planner;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;

/**
 * Data of the overview shown in the default tab of the form of a server.
 */
class GetServerOverview extends \Hubleto\Erp\Controllers\ApiController
{
  public function renderJson(): array
  {
    $idServer = $this->router()->urlParamAsInteger('idServer');
    if ($idServer <= 0) return ['status' => 'error', 'message' => $this->translate('Server was not specified.')];

    try {
      /** @var Server $mServer */
      $mServer = $this->getModel(Server::class);
      $t = $mServer->table;

      // reading through prepareReadQuery() checks that the current user may see the server
      $server = $mServer->record->prepareReadQuery()
        ->where($t . '.id', $idServer)
        ->with(['CUSTOMER', 'DATACENTER', 'OWNER', 'MANAGER', 'WORKFLOW_STEP'])
        ->first()?->toArray()
      ;
      if (!$server) return ['status' => 'error', 'message' => $this->translate('Server was not found or you have no access to it.')];

      /** @var Health $health */
      $health = $this->getService(Health::class);
      /** @var Settings $settings */
      $settings = $this->getService(Settings::class);
      /** @var Planner $planner */
      $planner = $this->getService(Planner::class);

      $latest = $health->getLatestForServers([$idServer])[$idServer] ?? null;
      $staleDays = $settings->getInt('staleHealthDays');
      $periodDays = max(1, $settings->getInt('reportDays'));

      $status = $latest ? $health->parseStatus($latest['status'] ?? null) : [];

      // history of health checks (oldest first, for the timeline)
      /** @var HealthStatus $mHealth */
      $mHealth = $this->getModel(HealthStatus::class);
      $historyRows = $mHealth->record
        ->where($mHealth->table . '.id_server', $idServer)
        ->orderBy($mHealth->table . '.datetime_checked', 'desc')
        ->orderBy($mHealth->table . '.id', 'desc')
        ->limit(30)
        ->get()?->toArray() ?? [];
      $history = [];
      foreach (array_reverse($historyRows) as $row) {
        $history[] = [
          'id' => (int) $row['id'],
          'checked_at' => (string) $row['datetime_checked'],
          'overall' => (int) $row['overall'],
          'label' => $health->label((int) $row['overall']),
          'color' => $health->color((int) $row['overall']),
        ];
      }

      // current application stack
      /** @var StackItem $mStack */
      $mStack = $this->getModel(StackItem::class);
      $stackRows = $mStack->record
        ->where($mStack->table . '.id_server', $idServer)
        ->where($mStack->table . '.is_current', 1)
        ->orderBy($mStack->table . '.name')
        ->get()?->toArray() ?? [];
      $stack = array_map(fn($r) => ['id' => (int) $r['id'], 'name' => (string) $r['name'], 'version' => (string) ($r['version'] ?? '')], $stackRows);

      // activities
      /** @var ServerActivity $mActivity */
      $mActivity = $this->getModel(ServerActivity::class);
      $activities = $mActivity->record
        ->where($mActivity->table . '.id_server', $idServer)
        ->where($mActivity->table . '.completed', 0)
        ->get()?->toArray() ?? [];

      $today = date('Y-m-d');
      $upcoming = [];
      $overdue = 0;
      foreach ($activities as $activity) {
        $next = $planner->nextOccurrence($activity, $today);
        if ($next !== null) {
          $upcoming[] = [
            'id' => (int) $activity['id'],
            'subject' => (string) $activity['subject'],
            'date' => $next,
            'time' => substr((string) ($activity['time_start'] ?? ''), 0, 5),
          ];
        } elseif (empty($activity['recurrence']) && (string) $activity['date_start'] < $today) {
          $overdue++;
        }
      }
      usort($upcoming, fn($a, $b) => strcmp($a['date'] . ' ' . $a['time'], $b['date'] . ' ' . $b['time']));

      // reports
      /** @var ActivityReport $mReport */
      $mReport = $this->getModel(ActivityReport::class);
      $since = date('Y-m-d', strtotime('-' . $periodDays . ' days'));
      $reportsInPeriod = (int) $mReport->record
        ->where($mReport->table . '.id_server', $idServer)
        ->where($mReport->table . '.activity_date', '>=', $since)
        ->count();
      $recentRows = $mReport->record
        ->where($mReport->table . '.id_server', $idServer)
        ->orderBy($mReport->table . '.activity_date', 'desc')
        ->orderBy($mReport->table . '.id', 'desc')
        ->limit(5)
        ->get()?->toArray() ?? [];
      $recentReports = array_map(fn($r) => [
        'id' => (int) $r['id'],
        'date' => (string) $r['activity_date'],
        'subject' => (string) $r['activity_subject'],
        'worker' => (string) $r['worker_name'],
        'has_followup' => trim((string) ($r['followup'] ?? '')) !== '',
      ], $recentRows);

      $location = trim(implode(' / ', array_filter([
        (string) ($server['DATACENTER']['name'] ?? ''),
        (string) ($server['room'] ?? ''),
        (string) ($server['rack'] ?? ''),
        (string) ($server['rack_position'] ?? ''),
      ])));

      return [
        'status' => 'success',
        'server' => [
          'id' => (int) $server['id'],
          'name' => (string) $server['name'],
          'is_closed' => (int) $server['is_closed'] === 1,
          'customer' => (string) ($server['CUSTOMER']['name'] ?? ''),
          'location' => $location,
          'workflow_step' => (string) ($server['WORKFLOW_STEP']['name'] ?? ''),
          'workflow_step_color' => (string) ($server['WORKFLOW_STEP']['color'] ?? ''),
        ],
        'health' => $latest ? [
          'overall' => (int) $latest['overall'],
          'label' => $health->label((int) $latest['overall']),
          'color' => $health->color((int) $latest['overall']),
          'checked_at' => (string) $latest['datetime_checked'],
          'note' => (string) ($latest['note'] ?? ''),
          'status' => $status,
          'stale' => $health->isStale((string) $latest['datetime_checked'], $staleDays),
        ] : null,
        'metrics' => $health->numericMetrics($status),
        'history' => $history,
        'stack' => $stack,
        'upcoming' => array_slice($upcoming, 0, 5),
        'overdue' => $overdue,
        'recent_reports' => $recentReports,
        'counts' => ['reports_period' => $reportsInPeriod, 'period_days' => $periodDays, 'stale_days' => $staleDays],
      ];
    } catch (\Throwable $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }
}
