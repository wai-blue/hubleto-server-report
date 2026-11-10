<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\External\WaiBlue\ServerReport\Loader as ServersApp;

/**
 * Typed access to settings of the app.
 * Settings are stored in the config under apps/Hubleto\App\External\WaiBlue\ServerReport/<key>.
 */
class Settings extends \Hubleto\Erp\Core
{
  public const CONFIG_PREFIX = 'apps/Hubleto\\App\\External\\WaiBlue\\Servers/';

  /** Default values of all settings. */
  public const DEFAULTS = [
    'detailDays' => 14,         // default number of days in the detailed dashboard
    'reportDays' => 90,         // number of days covered by the PDF server report
    'staleHealthDays' => 7,     // server without a health check for this number of days is reported as "stale"
    'rejectSecrets' => 1,       // reject connection details and configuration that look like they contain a password
    'autoDraftReport' => 1,     // create a draft of a report when an activity is completed
    'calendarColor' => '#0ea5e9',
  ];

  public function getString(string $key): string
  {
    $default = self::DEFAULTS[$key] ?? '';
    return $this->config()->forApp(ServersApp::class)->getAsString($key, (string) $default);
  }

  public function getInt(string $key): int
  {
    $default = self::DEFAULTS[$key] ?? 0;
    return $this->config()->forApp(ServersApp::class)->getAsInteger($key, (int) $default);
  }

  public function getBool(string $key): bool
  {
    $default = self::DEFAULTS[$key] ?? 0;
    return $this->config()->forApp(ServersApp::class)->getAsBool($key, (bool) $default);
  }

  /**
   * Saves a setting. The full config path is used because ConfigManager::save() ignores the prefix set by forApp().
   *
   * @param string $key
   * @param string|int|float|bool $value
   *
   * @return void
   *
   */
  public function save(string $key, string|int|float|bool $value): void
  {
    $this->config()->save(self::CONFIG_PREFIX . $key, is_bool($value) ? ($value ? '1' : '0') : (string) $value);
  }

  /**
   * All settings (with defaults), used by the settings page.
   *
   * @return array
   *
   */
  public function getAll(): array
  {
    $all = [];
    foreach (self::DEFAULTS as $key => $default) {
      $all[$key] = is_int($default) ? $this->getInt($key) : $this->getString($key);
    }
    return $all;
  }
}
