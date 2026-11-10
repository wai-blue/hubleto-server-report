<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers;

class ActivityTemplates extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'server-report', 'content' => $this->translate('Servers') ],
      [ 'url' => '', 'content' => $this->translate('Typical activities') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:ServerReport/ActivityTemplates.twig');
  }
}
