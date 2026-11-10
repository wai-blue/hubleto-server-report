<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\External\WaiBlue\ServerReport\Models\HealthStatus;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;

/**
 * Cheap counts shown in the sidebar badge, alerts and sidebar buttons. Reads only what the current user may see.
 */
class Counter extends \Hubleto\Erp\Core
{

  /**
   * Open servers whose latest health status is critical or down.
   *
   * @return int
   *
   */
  public function criticalServers(): int
  {
    /** @var Server $mServer */
    $mServer = $this->getModel(Server::class);
    $t = $mServer->table;

    return (int) $mServer->record->prepareReadQuery()
      ->where($t . '.is_closed', 0)
      ->whereRaw('IFNULL((' . HealthStatus::latestOverallSql($t) . '), 0) >= ?', [HealthStatus::OVERALL_CRITICAL])
      ->count()
    ;
  }

  /**
   * Open servers without a health check within the configured number of days.
   *
   * @return int
   *
   */
  public function staleServers(): int
  {
    /** @var Settings $settings */
    $settings = $this->getService(Settings::class);

    /** @var Server $mServer */
    $mServer = $this->getModel(Server::class);
    $t = $mServer->table;

    $limit = date('Y-m-d H:i:s', strtotime('-' . max(1, $settings->getInt('staleHealthDays')) . ' days'));

    return (int) $mServer->record->prepareReadQuery()
      ->where($t . '.is_closed', 0)
      ->whereRaw('IFNULL((' . HealthStatus::latestCheckedSql($t) . '), \'1970-01-01 00:00:00\') < ?', [$limit])
      ->count()
    ;
  }

  /**
   * Not completed and not recurring activities with the start date in the past.
   *
   * @return int
   *
   */
  public function overdueActivities(): int
  {
    /** @var ServerActivity $mActivity */
    $mActivity = $this->getModel(ServerActivity::class);
    $t = $mActivity->table;

    return (int) $mActivity->record->prepareReadQuery()
      ->where($t . '.completed', 0)
      ->where($t . '.date_start', '<', date('Y-m-d'))
      ->where(function ($q) use ($t) {
        $q->whereNull($t . '.recurrence')->orWhere($t . '.recurrence', '');
      })
      ->count()
    ;
  }
}
