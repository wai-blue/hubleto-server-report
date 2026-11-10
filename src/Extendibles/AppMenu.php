<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Extendibles;

class AppMenu extends \Hubleto\Framework\Extendible
{
  public function getItems(): array
  {
    return [
      [
        'app' => $this->app,
        'url' => 'server-report/board/health-overview',
        'title' => $this->app->translate('Servers: dashboard'),
        'icon' => 'fas fa-heart-pulse',
      ],
      [
        'app' => $this->app,
        'url' => 'server-report',
        'title' => $this->app->translate('Servers'),
        'icon' => 'fas fa-server',
      ],
      [
        'app' => $this->app,
        'url' => 'server-report/planner',
        'title' => $this->app->translate('Server activity planner'),
        'icon' => 'fas fa-calendar-check',
      ],
      [
        'app' => $this->app,
        'url' => 'server-report/reports',
        'title' => $this->app->translate('Server activity reports'),
        'icon' => 'fas fa-file-lines',
      ],
    ];
  }
}
