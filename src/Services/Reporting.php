<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityReport;
use Hubleto\App\External\WaiBlue\ServerReport\Models\HealthStatus;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;
use Hubleto\App\External\WaiBlue\ServerReport\Models\StackItem;

/**
 * Builds variables of the PDF server report.
 */
class Reporting extends \Hubleto\Erp\Core
{

  /**
   * Variables for the Twig template of the server report.
   *
   * @param int $idServer
   *
   * @return array
   *
   * @throws \Exception
   *
   */
  public function buildServerReportVars(int $idServer): array
  {
    /** @var Server $mServer */
    $mServer = $this->getModel(Server::class);
    $t = $mServer->table;

    // reading through prepareReadQuery() checks that the current user may see the server;
    // data that belong to the server (health, stack, reports) are then read by id of the server
    $server = $mServer->record->prepareReadQuery()
      ->where($t . '.id', $idServer)
      ->with(['OWNER', 'MANAGER', 'CUSTOMER', 'DATACENTER', 'DATACENTER.COUNTRY', 'PROVIDER', 'PROVIDER.COUNTRY', 'WORKFLOW_STEP', 'TAGS', 'TAGS.TAG'])
      ->first()
    ;
    if (!$server) throw new \Exception($this->translate('Server was not found or you have no access to it.'));

    $vars = $server->toArray();

    /** @var Settings $settings */
    $settings = $this->getService(Settings::class);
    $days = max(1, $settings->getInt('reportDays'));
    $since = date('Y-m-d', strtotime('-' . $days . ' days'));

    $vars['generated_at'] = date('Y-m-d H:i:s');
    $vars['period_days'] = $days;
    $vars['period_from'] = $since;

    $vars['owner_name'] = $this->userName($vars['OWNER'] ?? null);
    $vars['manager_name'] = $this->userName($vars['MANAGER'] ?? null);
    $vars['customer_name'] = (string) ($vars['CUSTOMER']['name'] ?? '');
    $vars['workflow_step_name'] = (string) ($vars['WORKFLOW_STEP']['name'] ?? '');
    $vars['tags'] = array_values(array_filter(array_map(fn($tag) => $tag['TAG']['name'] ?? '', $vars['TAGS'] ?? [])));

    $datacenter = $vars['DATACENTER'] ?? [];
    $vars['datacenter_name'] = (string) ($datacenter['name'] ?? '');
    $vars['datacenter_address'] = $this->address($datacenter);

    $provider = $vars['PROVIDER'] ?? [];
    $vars['provider_name'] = (string) ($provider['name'] ?? '');
    $vars['provider_address'] = $this->address($provider);
    $vars['provider_contact'] = trim(implode(', ', array_filter([
      (string) ($provider['contact_person'] ?? ''), (string) ($provider['email'] ?? ''), (string) ($provider['phone'] ?? ''),
    ])));
    $vars['provider_invoicing'] = trim(implode(', ', array_filter([
      (string) ($provider['invoicing_name'] ?? ''),
      !empty($provider['tax_id']) ? 'Tax ID ' . $provider['tax_id'] : '',
      !empty($provider['vat_id']) ? 'VAT ID ' . $provider['vat_id'] : '',
      (string) ($provider['invoicing_email'] ?? ''),
    ])));
    $vars['provider_invoicing_address'] = (string) ($provider['invoicing_address'] ?? '');

    /** @var Health $health */
    $health = $this->getService(Health::class);
    $latest = $health->getLatestForServers([$idServer])[$idServer] ?? null;
    $vars['health'] = $latest ? $this->formatHealth($latest, $health) : null;
    $vars['health_history'] = $this->healthHistory($idServer, $health, 10);
    $vars['stack'] = $this->currentStack($idServer, $health);
    $vars['activities'] = $this->activitiesSince($idServer, $since);

    return $vars;
  }

  /**
   * Formats a row of health status for the report.
   *
   * @param array $row
   * @param Health $health
   *
   * @return array
   *
   */
  private function formatHealth(array $row, Health $health): array
  {
    return [
      'overall' => (int) $row['overall'],
      'label' => $health->label((int) $row['overall']),
      'color' => $health->color((int) $row['overall']),
      'checked_at' => (string) $row['datetime_checked'],
      'status' => $health->parseStatus($row['status'] ?? null),
      'note' => (string) ($row['note'] ?? ''),
      'checked_by' => $this->userNameById((int) ($row['id_checked_by'] ?? 0)),
    ];
  }

  private function healthHistory(int $idServer, Health $health, int $limit): array
  {
    /** @var HealthStatus $mHealth */
    $mHealth = $this->getModel(HealthStatus::class);
    $rows = $mHealth->record
      ->where($mHealth->table . '.id_server', $idServer)
      ->orderBy($mHealth->table . '.datetime_checked', 'desc')
      ->orderBy($mHealth->table . '.id', 'desc')
      ->limit($limit)
      ->get()?->toArray() ?? [];

    return array_map(fn($row) => $this->formatHealth($row, $health), $rows);
  }

  private function currentStack(int $idServer, Health $health): array
  {
    /** @var StackItem $mStack */
    $mStack = $this->getModel(StackItem::class);
    $rows = $mStack->record
      ->where($mStack->table . '.id_server', $idServer)
      ->where($mStack->table . '.is_current', 1)
      ->orderBy($mStack->table . '.name')
      ->get()?->toArray() ?? [];

    $stack = [];
    foreach ($rows as $row) {
      $stack[] = [
        'name' => (string) $row['name'],
        'version' => (string) ($row['version'] ?? ''),
        'configuration' => $health->parseStatus($row['configuration'] ?? null),
        'recorded_at' => (string) ($row['datetime_recorded'] ?? ''),
      ];
    }
    return $stack;
  }

  /**
   * Activities performed since the given date: reports, and completed activities that have no report yet.
   *
   * @param int $idServer
   * @param string $since Y-m-d
   *
   * @return array
   *
   */
  private function activitiesSince(int $idServer, string $since): array
  {
    /** @var ActivityReport $mReport */
    $mReport = $this->getModel(ActivityReport::class);
    $reports = $mReport->record
      ->where($mReport->table . '.id_server', $idServer)
      ->where($mReport->table . '.activity_date', '>=', $since)
      ->orderBy($mReport->table . '.activity_date', 'desc')
      ->limit(300)
      ->get()?->toArray() ?? [];

    $list = [];
    $withReport = [];
    foreach ($reports as $report) {
      if ((int) $report['id_activity'] > 0) $withReport[(int) $report['id_activity']] = true;
      $list[] = [
        'date' => (string) $report['activity_date'],
        'time' => substr((string) ($report['activity_time'] ?? ''), 0, 5),
        'subject' => (string) $report['activity_subject'],
        'worker' => (string) $report['worker_name'],
        'steps' => (string) ($report['steps_performed'] ?? ''),
        'result' => (string) ($report['result'] ?? ''),
        'followup' => (string) ($report['followup'] ?? ''),
        'has_report' => true,
      ];
    }

    /** @var ServerActivity $mActivity */
    $mActivity = $this->getModel(ServerActivity::class);
    $completed = $mActivity->record
      ->with('OWNER')
      ->where($mActivity->table . '.id_server', $idServer)
      ->where($mActivity->table . '.completed', 1)
      ->where($mActivity->table . '.date_start', '>=', $since)
      ->get()?->toArray() ?? [];

    foreach ($completed as $activity) {
      if (isset($withReport[(int) $activity['id']])) continue;
      $list[] = [
        'date' => (string) $activity['date_start'],
        'time' => substr((string) ($activity['time_start'] ?? ''), 0, 5),
        'subject' => (string) $activity['subject'],
        'worker' => $this->userName($activity['OWNER'] ?? null),
        'steps' => '',
        'result' => '',
        'followup' => '',
        'has_report' => false,
      ];
    }

    usort($list, fn($a, $b) => strcmp($b['date'] . ' ' . $b['time'], $a['date'] . ' ' . $a['time']));
    return $list;
  }

  private function address(array $place): string
  {
    if (empty($place)) return '';
    return trim(implode(', ', array_filter([
      (string) ($place['street_line_1'] ?? ''),
      (string) ($place['street_line_2'] ?? ''),
      trim((string) ($place['postal_code'] ?? '') . ' ' . (string) ($place['city'] ?? '')),
      (string) ($place['COUNTRY']['name'] ?? ''),
    ])));
  }

  private function userName(?array $user): string
  {
    if (empty($user)) return '';
    $name = trim((string) ($user['first_name'] ?? '') . ' ' . (string) ($user['last_name'] ?? ''));
    if ($name === '') $name = (string) (($user['nick'] ?? '') ?: ($user['email'] ?? ''));
    return $name;
  }

  private function userNameById(int $idUser): string
  {
    if ($idUser <= 0) return '';
    /** @var \Hubleto\App\Community\Auth\Models\User $mUser */
    $mUser = $this->getModel(\Hubleto\App\Community\Auth\Models\User::class);
    $user = $mUser->record->where('id', $idUser)->first();
    return $user ? $this->userName($user->toArray()) : '';
  }
}
