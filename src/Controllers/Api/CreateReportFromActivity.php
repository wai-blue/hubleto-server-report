<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Api;

use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityReport;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;

/**
 * Creates a report of an activity (with the copied data of the activity) and returns its id.
 */
class CreateReportFromActivity extends \Hubleto\Erp\Controllers\ApiController
{
  public function renderJson(): array
  {
    $idActivity = $this->router()->urlParamAsInteger('idActivity');

    try {
      /** @var ServerActivity $mActivity */
      $mActivity = $this->getModel(ServerActivity::class);
      $activity = $mActivity->record->prepareReadQuery()->where($mActivity->table . '.id', $idActivity)->first();
      if (!$activity) return ['status' => 'error', 'message' => $this->translate('Activity was not found.')];

      /** @var Server $mServer */
      $mServer = $this->getModel(Server::class);
      $server = $mServer->record->prepareReadQuery()->where($mServer->table . '.id', (int) $activity->id_server)->first();
      if (!$server) return ['status' => 'error', 'message' => $this->translate('Server was not found or you have no access to it.')];

      /** @var ActivityReport $mReport */
      $mReport = $this->getModel(ActivityReport::class);
      $report = $mReport->record->recordCreate(['id_activity' => $idActivity]);
    } catch (\Throwable $e) {
      return ['status' => 'error', 'message' => $e->getMessage()];
    }

    return ['status' => 'success', 'idReport' => (int) $report['id']];
  }
}
