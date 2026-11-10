<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers;

class StackItems extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'server-report', 'content' => $this->translate('Servers') ],
      [ 'url' => '', 'content' => $this->translate('Application stack') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();
    $this->setView('@Hubleto:App:External:WaiBlue:ServerReport/StackItems.twig');
  }
}
