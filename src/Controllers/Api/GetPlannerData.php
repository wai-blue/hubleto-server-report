<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Api;

use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityTemplate;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Planner;

/**
 * Data of the planner page: typical activities with counts of planned activities and servers to plan them for.
 */
class GetPlannerData extends \Hubleto\Erp\Controllers\ApiController
{
  public function renderJson(): array
  {
    try {
      /** @var ActivityTemplate $mTemplate */
      $mTemplate = $this->getModel(ActivityTemplate::class);
      /** @var ServerActivity $mActivity */
      $mActivity = $this->getModel(ServerActivity::class);
      /** @var Server $mServer */
      $mServer = $this->getModel(Server::class);
      /** @var Planner $planner */
      $planner = $this->getService(Planner::class);

      $periodLabels = [
        ActivityTemplate::PERIOD_NONE => $this->translate('Once'),
        ActivityTemplate::PERIOD_DAY => $this->translate('day'),
        ActivityTemplate::PERIOD_WEEK => $this->translate('week'),
        ActivityTemplate::PERIOD_MONTH => $this->translate('month'),
        ActivityTemplate::PERIOD_YEAR => $this->translate('year'),
      ];

      $activities = $mActivity->record->prepareReadQuery()->whereNotNull($mActivity->table . '.id_template')->get()?->toArray() ?? [];
      $today = date('Y-m-d');
      $counts = [];
      foreach ($activities as $activity) {
        $idTemplate = (int) $activity['id_template'];
        if (!isset($counts[$idTemplate])) $counts[$idTemplate] = ['planned' => 0, 'upcoming' => 0, 'overdue' => 0];

        $isRecurring = !empty($activity['recurrence']);
        $next = $planner->nextOccurrence($activity, $today);
        if ($next !== null) {
          $counts[$idTemplate]['planned']++;
          $counts[$idTemplate]['upcoming']++;
        } elseif (!$isRecurring && (int) $activity['completed'] === 0 && (string) $activity['date_start'] < $today) {
          $counts[$idTemplate]['planned']++;
          $counts[$idTemplate]['overdue']++;
        }
      }

      $templates = [];
      $rows = $mTemplate->record->with('ACTIVITY_TYPE')->where($mTemplate->table . '.is_active', 1)->orderBy($mTemplate->table . '.name')->get()?->toArray() ?? [];
      foreach ($rows as $row) {
        $period = (int) $row['period'];
        $every = max(1, (int) $row['period_every']);
        $templates[] = [
          'id' => (int) $row['id'],
          'name' => (string) $row['name'],
          'description' => (string) ($row['description'] ?? ''),
          'type' => (string) ($row['ACTIVITY_TYPE']['name'] ?? ''),
          'color' => (string) ($row['ACTIVITY_TYPE']['color'] ?? '#6b7280'),
          'period' => $period,
          'repeats' => $period === ActivityTemplate::PERIOD_NONE
            ? $periodLabels[$period]
            : $this->translate('every') . ' ' . $every . ' ' . $periodLabels[$period] . ' x ' . (int) $row['period_count'],
          'duration_minutes' => (int) $row['duration_minutes'],
          'default_time' => substr((string) ($row['default_time'] ?? ''), 0, 5),
          'planned' => $counts[(int) $row['id']]['planned'] ?? 0,
          'upcoming' => $counts[(int) $row['id']]['upcoming'] ?? 0,
          'overdue' => $counts[(int) $row['id']]['overdue'] ?? 0,
        ];
      }

      $serverRows = $mServer->record->prepareReadQuery()
        ->where($mServer->table . '.is_closed', 0)
        ->orderBy($mServer->table . '.name')
        ->limit(500)
        ->get()?->toArray() ?? [];
      $servers = array_map(fn($s) => ['id' => (int) $s['id'], 'name' => (string) $s['name']], $serverRows);

      return ['status' => 'success', 'templates' => $templates, 'servers' => $servers];
    } catch (\Throwable $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }
  }
}
