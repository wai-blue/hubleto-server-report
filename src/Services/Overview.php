<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityReport;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;

/**
 * Data of the dashboards: health overview and detailed health with activities.
 */
class Overview extends \Hubleto\Erp\Core
{

  /**
   * Id of the user the dashboards are built for: the user with the given e-mail, otherwise the current user.
   *
   * @param string $email
   *
   * @return int
   *
   */
  public function resolveUserId(string $email = ''): int
  {
    $email = trim($email);
    if ($email !== '') {
      /** @var \Hubleto\App\Community\Auth\Models\User $mUser */
      $mUser = $this->getModel(\Hubleto\App\Community\Auth\Models\User::class);
      $user = $mUser->record->where('email', $email)->first();
      if ($user) return (int) $user->id;
    }
    return (int) $this->authProvider()->getUserId();
  }

  /**
   * Display name of a user.
   *
   * @param int $idUser
   *
   * @return string
   *
   */
  public function getUserName(int $idUser): string
  {
    /** @var \Hubleto\App\Community\Auth\Models\User $mUser */
    $mUser = $this->getModel(\Hubleto\App\Community\Auth\Models\User::class);
    $user = $mUser->record->where('id', $idUser)->first();
    if (!$user) return '';

    $name = trim((string) $user->first_name . ' ' . (string) $user->last_name);
    if ($name === '') $name = (string) ($user->nick ?: $user->email);
    return $name;
  }

  /**
   * Open servers where the user is the responsible administrator or the manager (and which the current user may see).
   *
   * @param int $idUser
   *
   * @return array
   *
   */
  public function getManagedServers(int $idUser): array
  {
    /** @var Server $mServer */
    $mServer = $this->getModel(Server::class);
    $t = $mServer->table;

    return $mServer->record->prepareReadQuery()
      ->where($t . '.is_closed', 0)
      ->where(function ($q) use ($t, $idUser) {
        $q->where($t . '.id_owner', $idUser)->orWhere($t . '.id_manager', $idUser);
      })
      ->with(['CUSTOMER', 'DATACENTER'])
      ->get()?->toArray() ?? [];
  }

  /**
   * Overall health of servers managed by the user.
   *
   * @param int $idUser
   *
   * @return array ['servers', 'summary', 'total']
   *
   */
  public function buildHealthOverview(int $idUser): array
  {
    /** @var Health $health */
    $health = $this->getService(Health::class);
    /** @var Settings $settings */
    $settings = $this->getService(Settings::class);

    $servers = $this->getManagedServers($idUser);
    $ids = array_map(fn($s) => (int) $s['id'], $servers);
    $latest = $health->getLatestForServers($ids);

    $rows = [];
    foreach ($servers as $server) {
      $row = $latest[(int) $server['id']] ?? null;
      $overall = $row ? (int) $row['overall'] : 0;
      $rows[] = [
        'id' => (int) $server['id'],
        'name' => (string) $server['name'],
        'customer' => (string) ($server['CUSTOMER']['name'] ?? ''),
        'datacenter' => (string) ($server['DATACENTER']['name'] ?? ''),
        'overall' => $overall,
        'label' => $health->label($overall),
        'color' => $health->color($overall),
        'checked_at' => $row ? (string) $row['datetime_checked'] : '',
        'stale' => $health->isStale($row ? (string) $row['datetime_checked'] : null, $settings->getInt('staleHealthDays')),
      ];
    }

    // the most severe servers first (unknown after down, before ok)
    $severity = [4 => 0, 3 => 1, 2 => 2, 0 => 3, 1 => 4];
    usort($rows, function ($a, $b) use ($severity) {
      $c = ($severity[$a['overall']] ?? 9) <=> ($severity[$b['overall']] ?? 9);
      return $c !== 0 ? $c : strcasecmp($a['name'], $b['name']);
    });

    return ['servers' => $rows, 'summary' => $health->summarize($latest, $ids), 'total' => count($rows)];
  }

  /**
   * Detailed health of servers managed by the user and activities performed in the last $days days.
   *
   * @param int $idUser
   * @param int $days
   *
   * @return array ['servers', 'summary', 'total', 'since', 'activityCount', 'followupCount']
   *
   */
  public function buildHealthDetail(int $idUser, int $days): array
  {
    /** @var Health $health */
    $health = $this->getService(Health::class);

    $data = $this->buildHealthOverview($idUser);
    $since = date('Y-m-d', strtotime('-' . max(1, $days) . ' days'));

    $ids = array_map(fn($s) => $s['id'], $data['servers']);
    $latest = $health->getLatestForServers($ids);

    /** @var ActivityReport $mReport */
    $mReport = $this->getModel(ActivityReport::class);
    /** @var ServerActivity $mActivity */
    $mActivity = $this->getModel(ServerActivity::class);

    $activityCount = 0;
    $followupCount = 0;

    foreach ($data['servers'] as $key => $server) {
      $row = $latest[$server['id']] ?? null;
      $status = $row ? $health->parseStatus($row['status'] ?? null) : [];
      $data['servers'][$key]['metrics'] = $health->numericMetrics($status);
      $data['servers'][$key]['status'] = $status;
      $data['servers'][$key]['note'] = $row ? (string) ($row['note'] ?? '') : '';

      $reports = $mReport->record
        ->where($mReport->table . '.id_server', $server['id'])
        ->where($mReport->table . '.activity_date', '>=', $since)
        ->orderBy($mReport->table . '.activity_date', 'desc')
        ->get()?->toArray() ?? [];

      $completedWithoutReport = (int) $mActivity->record
        ->where($mActivity->table . '.id_server', $server['id'])
        ->where($mActivity->table . '.completed', 1)
        ->where($mActivity->table . '.date_start', '>=', $since)
        ->whereNotIn($mActivity->table . '.id', array_map(fn($r) => (int) $r['id_activity'], $reports) ?: [0])
        ->count();

      $followups = array_values(array_filter($reports, fn($r) => trim((string) ($r['followup'] ?? '')) !== ''));

      $data['servers'][$key]['activity_count'] = count($reports) + $completedWithoutReport;
      $data['servers'][$key]['followup_count'] = count($followups);
      $data['servers'][$key]['recent_reports'] = array_map(fn($r) => [
        'date' => (string) $r['activity_date'],
        'subject' => (string) $r['activity_subject'],
        'worker' => (string) $r['worker_name'],
        'result' => (string) ($r['result'] ?? ''),
        'has_followup' => trim((string) ($r['followup'] ?? '')) !== '',
      ], array_slice($reports, 0, 3));

      $activityCount += $data['servers'][$key]['activity_count'];
      $followupCount += count($followups);
    }

    $data['since'] = $since;
    $data['days'] = max(1, $days);
    $data['activityCount'] = $activityCount;
    $data['followupCount'] = $followupCount;

    return $data;
  }
}
