<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Api;

use Hubleto\App\External\WaiBlue\ServerReport\Services\Planner;

/**
 * Plans an activity (from a typical activity) on one or more servers.
 */
class PlanActivities extends \Hubleto\Erp\Controllers\ApiController
{
  public function renderJson(): array
  {
    $idTemplate = $this->router()->urlParamAsInteger('idTemplate');
    $idServers = array_filter(array_map('intval', explode(',', $this->router()->urlParamAsString('idServers'))));
    $dateStart = $this->router()->urlParamAsString('dateStart');
    $timeStart = $this->router()->urlParamAsString('timeStart');

    if ($idTemplate <= 0 || empty($idServers)) {
      return ['status' => 'error', 'message' => $this->translate('Select a typical activity and at least one server.')];
    }
    if ($timeStart !== '' && !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $timeStart)) {
      return ['status' => 'error', 'message' => $this->translate('Start time must be in the format HH:MM.')];
    }

    try {
      /** @var Planner $planner */
      $planner = $this->getService(Planner::class);
      $created = $planner->plan($idTemplate, $idServers, $dateStart, $timeStart);
    } catch (\Throwable $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }

    return ['status' => 'success', 'created' => count($created), 'ids' => $created];
  }
}
