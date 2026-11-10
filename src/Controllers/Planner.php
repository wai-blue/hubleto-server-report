<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers;

class Planner extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'server-report', 'content' => $this->translate('Servers') ],
      [ 'url' => 'server-report/planner', 'content' => $this->translate('Planner') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:ServerReport/Planner.twig');
  }
}
