<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers\Boards;

use Hubleto\App\External\WaiBlue\ServerReport\Services\Overview;
use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;

class HealthDetail extends \Hubleto\App\Community\Dashboards\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'server-report', 'content' => $this->translate('Servers') ],
      [ 'url' => 'server-report/board/health-detail', 'content' => $this->translate('Detailed dashboard') ],
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
      [
        'variable' => 'days',
        'type' => 'number',
        'default' => 14,
        'description' => $this->translate('Number of days of performed activities. Empty = the value from the settings of the app (14 by default).'),
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

    $days = $this->router()->urlParamAsInteger('days');
    if ($days <= 0) $days = $settings->getInt('detailDays');
    $days = max(1, min(3650, $days));

    $data = $overview->buildHealthDetail($idUser, $days);
    $this->viewParams['servers'] = $data['servers'];
    $this->viewParams['summary'] = $data['summary'];
    $this->viewParams['total'] = $data['total'];
    $this->viewParams['days'] = $data['days'];
    $this->viewParams['since'] = $data['since'];
    $this->viewParams['activityCount'] = $data['activityCount'];
    $this->viewParams['followupCount'] = $data['followupCount'];

    $this->viewParams['userName'] = $overview->getUserName($idUser);
    $this->viewParams['projectUrl'] = $this->env()->projectUrl;
    $this->setView('@Hubleto:App:External:WaiBlue:ServerReport/Boards/HealthDetail.twig');
  }
}
