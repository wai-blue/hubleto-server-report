<?php declare(strict_types=1);

namespace Hubleto\App\External\WaiBlue\ServerReport\Tests;

use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityReport;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ActivityTemplate;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Datacenter;
use Hubleto\App\External\WaiBlue\ServerReport\Models\HealthStatus;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Provider;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Server;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerActivity;
use Hubleto\App\External\WaiBlue\ServerReport\Models\ServerHasTag;
use Hubleto\App\External\WaiBlue\ServerReport\Models\StackItem;
use Hubleto\App\External\WaiBlue\ServerReport\Models\Tag;

final class RenderAllRoutesTest extends \Hubleto\Erp\TestCase
{

  public function testCrudRouteForModel(): void
  {
    $this->_testCrudRouteForModel(Server::class, 'server-report');
    $this->_testCrudRouteForModel(Provider::class, 'server-report/providers');
    $this->_testCrudRouteForModel(Datacenter::class, 'server-report/datacenters');
    $this->_testCrudRouteForModel(Tag::class, 'server-report/tags');
    $this->_testCrudRouteForModel(ServerHasTag::class, 'server-report/server-has-tags');
    $this->_testCrudRouteForModel(StackItem::class, 'server-report/stack');
    $this->_testCrudRouteForModel(HealthStatus::class, 'server-report/health');
    $this->_testCrudRouteForModel(ActivityTemplate::class, 'server-report/activity-templates');
    $this->_testCrudRouteForModel(ServerActivity::class, 'server-report/activities');
    $this->_testCrudRouteForModel(ActivityReport::class, 'server-report/reports');
  }

  public function testPages(): void
  {
    $this->_testRouteContainsAppMainTitle('server-report/planner');
    $this->_testRouteDoesNotContainError('server-report/planner');
    $this->_testRouteContainsAppMainTitle('server-report/settings');
    $this->_testRouteDoesNotContainError('server-report/settings');
    $this->_testRouteDoesNotContainError('server-report/board/health-overview');
    $this->_testRouteDoesNotContainError('server-report/board/health-detail');
  }

  public function testApiRoutes(): void
  {
    $this->_testApiRouteReturnsJson('server-report/api/get-server-overview', ['idServer' => 1]);
    $this->_testApiRouteReturnsJson('server-report/api/get-planner-data');
    $this->_testApiRouteReturnsJson('server-report/api/generate-report', ['idServer' => 1]);
    $this->_testApiRouteReturnsJson('server-report/api/plan-activities', ['idTemplate' => 1, 'idServers' => '1', 'dateStart' => date('Y-m-d')]);
    $this->_testApiRouteReturnsJson('server-report/api/log-activity', ['idServer' => 1, 'activity' => 'test']);
    $this->_testApiRouteReturnsJson('server-report/api/create-report-from-activity', ['idActivity' => 1]);
  }

}
