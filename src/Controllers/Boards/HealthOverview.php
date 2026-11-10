<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Boards;

use Hubleto\App\External\WaiBlue\ServerReport\Services\Overview;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;

class HealthOverview extends \Hubleto\App\Community\Dashboards\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'server-report', 'content' => $this->translate('Servers') ],
      [ 'url' => 'server-report/board/health-overview', 'content' => $this->translate('Dashboard') ],
    ]);
  }

  public function getConfigurationOptionsDescription(): array
  {
    return [
      [
        'variable' => 'userEmail',
        'type' => 'email',
        'default' => '',
        'description' => $this->translate('E-mail of the user whose servers are shown. Empty = the current user.'),
      ],
    ];
  }

  public function prepareView(): void
  {
    parent::prepareView();

    /** @var Overview $overview */
    $overview = $this->getService(Overview::class);
    /** @var Settings $settings */
    $settings = $this->getService(Settings::class);

    $idUser = $overview->resolveUserId($this->router()->urlParamAsString('userEmail'));

    $data = $overview->buildHealthOverview($idUser);
    $this->viewParams['servers'] = $data['servers'];
    $this->viewParams['summary'] = $data['summary'];
    $this->viewParams['total'] = $data['total'];

    $this->viewParams['userName'] = $overview->getUserName($idUser);
    $this->viewParams['projectUrl'] = $this->env()->projectUrl;
    $this->setView('@Hubleto:App:External:WaiBlue:ServerReport/Boards/HealthOverview.twig');
  }
}
