<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityTemplate;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;

/**
 * Plans activities on servers from typical activities (templates).
 */
class Planner extends \Hubleto\Erp\Core
{
  private const MAX_SERVERS_AT_ONCE = 50;

  /**
   * Expands a recurrence into a list of dates. The first date is the start date.
   * Months and years keep the day of the month (shortened for shorter months).
   *
   * @param string $period One of day, week, month, year
   * @param int $every
   * @param int $count
   * @param string $dateStart Y-m-d
   *
   * @return array
   *
   */
  public function expandDates(string $period, int $every, int $count, string $dateStart): array
  {
    $dates = [];
    $start = new \DateTime($dateStart);
    $startDay = (int) $start->format('j');
    $every = max(1, $every);
    $count = max(1, min(366, $count));

    for ($i = 0; $i < $count; $i++) {
      $n = $i * $every;

      if ($period === 'day') {
        $d = (clone $start)->modify('+' . $n . ' days');
      } elseif ($period === 'week') {
        $d = (clone $start)->modify('+' . ($n * 7) . ' days');
      } elseif ($period === 'month' || $period === 'year') {
        $months = $period === 'year' ? $n * 12 : $n;
        $d = (clone $start)->modify('first day of this month')->modify('+' . $months . ' months');
        $d->setDate((int) $d->format('Y'), (int) $d->format('n'), min($startDay, (int) $d->format('t')));
      } else {
        $d = clone $start;
      }

      $dates[] = $d->format('Y-m-d');
    }

    return $dates;
  }

  /**
   * Next date of an activity on or after $from (looks into recurrence, if the activity has one).
   *
   * @param array $activity
   * @param string $from Y-m-d
   *
   * @return string|null
   *
   */
  public function nextOccurrence(array $activity, string $from): ?string
  {
    $recurrence = !empty($activity['recurrence']) ? json_decode((string) $activity['recurrence'], true) : null;

    if (is_array($recurrence) && is_array($recurrence['dates'] ?? null)) {
      $dates = $recurrence['dates'];
      sort($dates);
      foreach ($dates as $date) {
        if ((string) $date >= $from) return (string) $date;
      }
      return null;
    }

    $date = (string) ($activity['date_start'] ?? '');
    if ($date !== '' && $date >= $from && (int) ($activity['completed'] ?? 0) === 0) return $date;
    return null;
  }

  /**
   * Creates one activity (recurring, if the template says so) for each of the selected servers.
   *
   * @param int $idTemplate
   * @param array $idServers
   * @param string $dateStart Y-m-d
   * @param string $timeStart H:i or H:i:s, may be empty
   * @param int $idOwner User who is responsible for the activities, 0 = the owner of the server
   *
   * @return array Ids of created activities
   *
   * @throws \Exception
   *
   */
  public function plan(int $idTemplate, array $idServers, string $dateStart, string $timeStart = '', int $idOwner = 0): array
  {
    $parsed = \DateTime::createFromFormat('Y-m-d', $dateStart);
    if (!$parsed || $parsed->format('Y-m-d') !== $dateStart) {
      throw new \Exception($this->translate('Start date must be in the format YYYY-MM-DD.'));
    }

    /** @var ActivityTemplate $mTemplate */
    $mTemplate = $this->getModel(ActivityTemplate::class);
    $template = $mTemplate->record->where($mTemplate->table . '.id', $idTemplate)->first();
    if (!$template) throw new \Exception($this->translate('Typical activity was not found.'));

    $idServers = array_slice(array_values(array_unique(array_filter(array_map('intval', $idServers)))), 0, self::MAX_SERVERS_AT_ONCE);

    /** @var Server $mServer */
    $mServer = $this->getModel(Server::class);
    $servers = $mServer->record->prepareReadQuery()->whereIn($mServer->table . '.id', $idServers)->get();
    if ($servers->isEmpty()) throw new \Exception($this->translate('No accessible server was selected.'));

    $periodNames = [
      ActivityTemplate::PERIOD_DAY => 'day',
      ActivityTemplate::PERIOD_WEEK => 'week',
      ActivityTemplate::PERIOD_MONTH => 'month',
      ActivityTemplate::PERIOD_YEAR => 'year',
    ];
    $period = $periodNames[(int) $template->period] ?? null;

    $time = trim($timeStart) !== '' ? trim($timeStart) : trim((string) $template->default_time);
    if ($time !== '' && strlen($time) === 5) $time .= ':00';
    $duration = max(0, (int) $template->duration_minutes);

    /** @var ServerActivity $mActivity */
    $mActivity = $this->getModel(ServerActivity::class);
    $created = [];

    foreach ($servers as $server) {
      $row = [
        'subject' => $template->name . ' - ' . $server->name,
        'description' => (string) $template->description,
        'date_start' => $dateStart,
        'completed' => 0,
        'id_owner' => $idOwner > 0 ? $idOwner : (int) ($server->id_owner ?: $this->authProvider()->getUserId()),
        'id_server' => (int) $server->id,
        'id_template' => (int) $template->id,
      ];

      if ((int) $template->id_activity_type > 0) $row['id_activity_type'] = (int) $template->id_activity_type;

      if ($time !== '') {
        $end = (new \DateTime($dateStart . ' ' . $time))->modify('+' . $duration . ' minutes');
        $row['time_start'] = $time;
        $row['date_end'] = $end->format('Y-m-d');
        $row['time_end'] = $end->format('H:i:s');
        $row['all_day'] = 0;
      } else {
        $row['date_end'] = $dateStart;
        $row['all_day'] = 1;
      }

      if ($period !== null) {
        $row['recurrence'] = json_encode([
          'period' => $period,
          'periodEvery' => max(1, (int) $template->period_every),
          'periodCount' => max(1, (int) $template->period_count),
          'dates' => $this->expandDates($period, (int) $template->period_every, (int) $template->period_count, $dateStart),
        ]);
      }

      $activity = $mActivity->record->recordCreate($row);
      $created[] = (int) $activity['id'];
    }

    return $created;
  }
}
