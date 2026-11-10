<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\External\WaiBlue\ServerReport\Models\HealthStatus;

/**
 * Everything about health statuses of servers: labels, colors, latest status of servers and summaries.
 */
class Health extends \Hubleto\Erp\Core
{

  /**
   * @return array Translated labels of overall statuses
   */
  public function labels(): array
  {
    return [
      HealthStatus::OVERALL_OK => $this->translate('OK'),
      HealthStatus::OVERALL_WARNING => $this->translate('Warning'),
      HealthStatus::OVERALL_CRITICAL => $this->translate('Critical'),
      HealthStatus::OVERALL_DOWN => $this->translate('Down'),
      HealthStatus::OVERALL_UNKNOWN => $this->translate('Unknown'),
    ];
  }

  /**
   * @return array Colors (hex) of overall statuses
   */
  public function colors(): array
  {
    return [
      HealthStatus::OVERALL_OK => '#16a34a',
      HealthStatus::OVERALL_WARNING => '#ca8a04',
      HealthStatus::OVERALL_CRITICAL => '#ea580c',
      HealthStatus::OVERALL_DOWN => '#dc2626',
      HealthStatus::OVERALL_UNKNOWN => '#6b7280',
    ];
  }

  public function label(int $overall): string
  {
    return $this->labels()[$overall] ?? $this->translate('Unknown');
  }

  public function color(int $overall): string
  {
    return $this->colors()[$overall] ?? '#6b7280';
  }

  /**
   * Converts the JSON of status details into a flat array. Empty values are dropped.
   *
   * @param mixed $json
   *
   * @return array
   *
   */
  public function parseStatus(mixed $json): array
  {
    if (is_string($json)) $json = json_decode($json, true);
    if (!is_array($json)) return [];

    $parsed = [];
    foreach ($json as $key => $value) {
      if (is_array($value)) $value = json_encode($value);
      if (trim((string) $value) === '') continue;
      $parsed[(string) $key] = $value;
    }
    return $parsed;
  }

  /**
   * Numeric values of status details (cpu_percent, memory_percent, ...).
   *
   * @param array $status Parsed status details
   *
   * @return array
   *
   */
  public function numericMetrics(array $status): array
  {
    $metrics = [];
    foreach ($status as $key => $value) {
      if (is_numeric($value)) $metrics[$key] = (float) $value;
    }
    return $metrics;
  }

  /**
   * Latest health status of each of the given servers.
   *
   * @param array $idServers
   *
   * @return array [idServer => row of the latest health status]
   *
   */
  public function getLatestForServers(array $idServers): array
  {
    $idServers = array_values(array_filter(array_map('intval', $idServers)));
    if (empty($idServers)) return [];

    /** @var HealthStatus $mHealth */
    $mHealth = $this->getModel(HealthStatus::class);
    $t = $mHealth->table;

    $rows = $mHealth->record
      ->whereIn($t . '.id_server', $idServers)
      ->whereRaw(
        $t . '.id = (SELECT h2.id FROM `' . $t . '` h2 WHERE h2.id_server = ' . $t . '.id_server '
        . 'ORDER BY h2.datetime_checked DESC, h2.id DESC LIMIT 1)'
      )
      ->get()?->toArray() ?? [];

    $latest = [];
    foreach ($rows as $row) {
      $latest[(int) $row['id_server']] = $row;
    }
    return $latest;
  }

  /**
   * Counts servers per overall status. Servers without any health check are counted as unknown.
   *
   * @param array $latestByServer Result of getLatestForServers()
   * @param array $idServers
   *
   * @return array
   *
   */
  public function summarize(array $latestByServer, array $idServers): array
  {
    $counts = [];
    foreach (array_keys($this->labels()) as $overall) $counts[$overall] = 0;

    foreach ($idServers as $idServer) {
      $overall = isset($latestByServer[(int) $idServer]) ? (int) $latestByServer[(int) $idServer]['overall'] : HealthStatus::OVERALL_UNKNOWN;
      if (!isset($counts[$overall])) $overall = HealthStatus::OVERALL_UNKNOWN;
      $counts[$overall]++;
    }

    $summary = [];
    foreach ($counts as $overall => $count) {
      $summary[] = ['overall' => $overall, 'label' => $this->label($overall), 'color' => $this->color($overall), 'count' => $count];
    }
    return $summary;
  }

  /**
   * Is the datetime of the last check older than the given number of days?
   *
   * @param string|null $datetimeChecked
   * @param int $days
   *
   * @return bool
   *
   */
  public function isStale(?string $datetimeChecked, int $days): bool
  {
    if (empty($datetimeChecked)) return true;
    $time = strtotime($datetimeChecked);
    if ($time === false) return true;
    return $time < strtotime('-' . max(1, $days) . ' days');
  }
}
