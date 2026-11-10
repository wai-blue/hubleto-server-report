<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Api;

use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;

/**
 * Logs an already completed activity on a server (the quick "log activity" box of the Calendar tab).
 */
class LogActivity extends \Hubleto\Erp\Controllers\ApiController
{
  public function renderJson(): array
  {
    $idServer = $this->router()->urlParamAsInteger('idServer');
    $activity = trim($this->router()->urlParamAsString('activity'));

    if ($idServer > 0 && $activity !== '') {
      /** @var Server $mServer */
      $mServer = $this->getModel(Server::class);

      // reading through prepareReadQuery() checks that the current user may see the server
      $server = $mServer->record->prepareReadQuery()->where($mServer->table . '.id', $idServer)->first();

      if ($server) {
        /** @var ServerActivity $mActivity */
        $mActivity = $this->getModel(ServerActivity::class);
        $mActivity->record->recordCreate([
          'id_server' => $idServer,
          'subject' => $activity,
          'date_start' => date('Y-m-d'),
          'time_start' => date('H:i:s'),
          'all_day' => true,
          'completed' => true,
          'id_owner' => $this->authProvider()->getUserId(),
        ]);
      } else {
        return ['status' => 'error', 'message' => $this->translate('Server was not found or you have no access to it.')];
      }
    }

    return ['status' => 'success', 'idServer' => $idServer];
  }
}
