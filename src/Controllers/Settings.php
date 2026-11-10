<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Controllers;

use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings as SettingsService;

class Settings extends \Hubleto\Erp\Controller
{
  public function getBreadcrumbs(): array
  {
    return array_merge(parent::getBreadcrumbs(), [
      [ 'url' => 'server-report', 'content' => $this->translate('Servers') ],
      [ 'url' => 'server-report/settings', 'content' => $this->translate('Settings') ],
    ]);
  }

  public function prepareView(): void
  {
    parent::prepareView();

    /** @var SettingsService $settings */
    $settings = $this->getService(SettingsService::class);

    if ($this->router()->urlParamAsBool('settingsChanged')) {
      foreach (['detailDays', 'reportDays', 'staleHealthDays'] as $key) {
        $settings->save($key, max(1, min(3650, $this->router()->urlParamAsInteger($key))));
      }
      foreach (['rejectSecrets', 'autoDraftReport'] as $key) {
        $settings->save($key, $this->router()->urlParamAsInteger($key) === 1 ? 1 : 0);
      }
      $color = $this->router()->urlParamAsString('calendarColor');
      if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $settings->save('calendarColor', $color);
      }
      $this->viewParams['settingsSaved'] = true;
    }

    $this->viewParams['settings'] = $settings->getAll();
    $this->viewParams['projectUrl'] = $this->env()->projectUrl;
    $this->setView('@Hubleto:App:External:WaiBlue:ServerReport/Settings.twig');
  }
}
