<?php

namespace Hubleto\App\External\WaiBlue\ServerReport;

use Hubleto\App\External\WaiBlue\ServerReport\Services\Settings;

class Calendar extends \Hubleto\App\Community\Calendar\Calendar
{

  /**
   * Configuration of the calendar of server reports.
   *
   * @return array
   *
   */
  public function getCalendarConfig(): array
  {
    /** @var Settings $settings */
    $settings = $this->getService(Settings::class);

    return [
      'position' => 6,
      'color' => $settings->getString('calendarColor'),
      'title' => $this->translate('Servers'),
      'addNewActivityButtonText' => $this->translate('Plan new activity on a server'),
      'icon' => 'fas fa-server',
      'formComponent' => 'ServersFormServerActivity',
    ];
  }

  /**
   * Loads one activity.
   *
   * @param int $id
   *
   * @return array
   *
   */
  public function loadEvent(int $id): array
  {
    return $this->prepareLoadActivityQuery($this->getModel(Models\ServerActivity::class), $id)->first()?->toArray();
  }

  /**
   * Loads activities of server reports in the given period. When `idServer` is given in the URL, only activities of that server are loaded.
   *
   * @param string $dateStart
   * @param string $dateEnd
   * @param array $filter
   *
   * @return array
   *
   */
  public function loadEvents(string $dateStart, string $dateEnd, array $filter = []): array
  {
    $idServer = $this->router()->urlParamAsInteger('idServer');

    /** @var Models\ServerActivity $mActivity */
    $mActivity = $this->getModel(Models\ServerActivity::class);
    $activities = $this->prepareLoadActivitiesQuery($mActivity, $dateStart, $dateEnd, $filter)->with('SERVER');
    if ($idServer > 0) {
      $activities = $activities->where($mActivity->table . '.id_server', $idServer);
    }

    $events = $this->convertActivitiesToEvents(
      'server-report',
      $activities->get()?->toArray(),
      function (array $activity) {
        if (isset($activity['SERVER'])) {
          return $this->translate('Server') . ' ' . ($activity['SERVER']['name'] ?? '');
        } else {
          return '';
        }
      }
    );

    return $events;
  }

}
