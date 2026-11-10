<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Services;

use Hubleto\App\Community\Auth\Models\User;
use Hubleto\App\Community\Customers\Models\Customer;
use Hubleto\App\Community\Settings\Models\ActivityType;
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

/**
 * Demo data: servers with tags, application stack history, two weeks of health checks, planned and performed activities.
 * Does nothing when there already are servers, so it never mixes demo data with real data.
 */
class DemoData extends \Hubleto\Erp\Core
{

  public function generate(): void
  {
    /** @var Server $mServer */
    $mServer = $this->getModel(Server::class);
    if ($mServer->record->count() > 0) return;

    /** @var DefaultData $defaultData */
    $defaultData = $this->getService(DefaultData::class);
    $defaultData->ensure();

    /** @var \Hubleto\App\Community\Auth\Models\User $mUser */
    $mUser = $this->getModel(User::class);
    $users = $mUser->record->orderBy('id')->limit(3)->get()?->toArray() ?? [];
    $idAdmin = (int) ($users[0]['id'] ?? $this->authProvider()->getUserId());
    $idManager = (int) ($users[1]['id'] ?? $idAdmin);

    /** @var Customer $mCustomer */
    $mCustomer = $this->getModel(Customer::class);
    $customers = $mCustomer->record->orderBy('id')->limit(2)->get()?->toArray() ?? [];
    $idCustomer1 = (int) ($customers[0]['id'] ?? 0) ?: null;
    $idCustomer2 = (int) ($customers[1]['id'] ?? 0) ?: $idCustomer1;

    $idDc1 = $this->create(Datacenter::class, [
      'name' => 'Example DC1', 'street_line_1' => 'Datacenter Street 12', 'city' => 'Bratislava', 'postal_code' => '811 01',
      'access_info' => "Reception 24/7, bring an ID.\nOn-site contact: +421 900 000 001",
    ]);
    $idDc2 = $this->create(Datacenter::class, [
      'name' => 'Example DC2', 'street_line_1' => 'Rechenzentrum Allee 5', 'city' => 'Frankfurt', 'postal_code' => '60311',
      'access_info' => 'Remote hands available, open a ticket in the provider portal.',
    ]);

    $idProvider1 = $this->create(Provider::class, [
      'name' => 'Acme Hardware Ltd.', 'contact_person' => 'Jane Seller', 'email' => 'sales@acme-hardware.example', 'phone' => '+421 900 000 010',
      'website' => 'https://acme-hardware.example', 'street_line_1' => 'Industrial Road 7', 'city' => 'Bratislava', 'postal_code' => '821 09',
      'invoicing_name' => 'Acme Hardware Ltd.', 'invoicing_address' => "Industrial Road 7\n821 09 Bratislava", 'tax_id' => '12345678',
      'vat_id' => 'SK1234567890', 'invoicing_email' => 'invoices@acme-hardware.example',
    ]);
    $idProvider2 = $this->create(Provider::class, [
      'name' => 'Contoso Servers GmbH', 'contact_person' => 'Hans Support', 'email' => 'support@contoso-servers.example', 'phone' => '+49 69 000000',
      'website' => 'https://contoso-servers.example', 'street_line_1' => 'Hauptstrasse 1', 'city' => 'Frankfurt', 'postal_code' => '60311',
      'invoicing_name' => 'Contoso Servers GmbH', 'invoicing_address' => "Hauptstrasse 1\n60311 Frankfurt", 'vat_id' => 'DE123456789',
      'invoicing_email' => 'billing@contoso-servers.example',
    ]);

    $tags = [];
    foreach ([['production', '#16a34a'], ['staging', '#ca8a04'], ['database', '#7c3aed'], ['web', '#0ea5e9'], ['backup', '#6b7280']] as [$name, $color]) {
      $tags[$name] = $this->create(Tag::class, ['name' => $name, 'color' => $color]);
    }

    // name, description, datacenter, provider, customer, tags, room, rack, position, overall of the latest check, closed
    $servers = [
      ['web-01', 'Public web server of the main e-shop.', $idDc1, $idProvider1, $idCustomer1, ['production', 'web'], 'Room A', 'R12', 'U10-U11', 1, false],
      ['web-02', 'Second web server (load balanced).', $idDc1, $idProvider1, $idCustomer1, ['production', 'web'], 'Room A', 'R12', 'U12-U13', 2, false],
      ['db-01', 'Primary PostgreSQL database.', $idDc1, $idProvider1, $idCustomer1, ['production', 'database'], 'Room A', 'R14', 'U20-U23', 3, false],
      ['backup-01', 'Backup target for all production servers.', $idDc2, $idProvider2, $idCustomer2, ['backup'], 'Hall 2', 'R03', 'U05', 1, false],
      ['mail-01', 'Mail relay for customer notifications.', $idDc2, $idProvider2, $idCustomer2, ['production'], 'Hall 2', 'R03', 'U07', 4, false],
      ['legacy-app-01', 'Legacy application server, decommissioned.', $idDc1, $idProvider1, $idCustomer2, ['staging'], 'Room B', 'R21', 'U01-U02', 0, true],
    ];

    $metricsByOverall = [
      0 => [],
      1 => ['cpu_percent' => 22, 'memory_percent' => 48, 'disk_percent' => 41],
      2 => ['cpu_percent' => 71, 'memory_percent' => 86, 'disk_percent' => 78],
      3 => ['cpu_percent' => 93, 'memory_percent' => 91, 'disk_percent' => 94],
      4 => [],
    ];

    $stacks = [
      'web-01' => [['nginx', ['1.24.0', '1.26.1']], ['php-fpm', ['8.2.12', '8.3.6']], ['Debian', ['12.4', '12.7']]],
      'web-02' => [['nginx', ['1.26.1']], ['php-fpm', ['8.3.6']], ['Debian', ['12.7']]],
      'db-01' => [['PostgreSQL', ['15.4', '16.2']], ['pgbackrest', ['2.50']], ['Debian', ['12.7']]],
      'backup-01' => [['restic', ['0.16.2']], ['Ubuntu', ['22.04', '24.04']]],
      'mail-01' => [['Postfix', ['3.7.10']], ['Dovecot', ['2.3.19']]],
      'legacy-app-01' => [['Tomcat', ['8.5.40']], ['CentOS', ['7.9']]],
    ];

    $idsServers = [];
    foreach ($servers as [$name, $description, $idDc, $idProvider, $idCustomer, $serverTags, $room, $rack, $position, $overall, $closed]) {
      $server = $mServer->record->recordCreate([
        'name' => $name,
        'description' => $description,
        'connection_details' => 'SSH via bastion.example.com (port 2222), user "admin", key-based login only. VPN profile: corp-vpn.',
        'doc_url_1' => 'https://wiki.example.com/server-report/' . $name,
        'doc_url_2' => 'https://runbooks.example.com/' . $name,
        'is_closed' => $closed ? 1 : 0,
        'id_owner' => $idAdmin,
        'id_manager' => $idManager,
        'id_customer' => $idCustomer,
        'id_datacenter' => $idDc,
        'room' => $room,
        'rack' => $rack,
        'rack_position' => $position,
        'id_provider' => $idProvider,
        'note' => 'Demo server.',
      ]);
      $idServer = (int) $server['id'];
      $idsServers[$name] = $idServer;

      foreach ($serverTags as $tagName) {
        $this->create(ServerHasTag::class, ['id_server' => $idServer, 'id_tag' => $tags[$tagName]]);
      }

      // application stack with history: older versions first, the last one is current
      foreach ($stacks[$name] as $k => [$app, $versions]) {
        foreach ($versions as $i => $version) {
          $this->create(StackItem::class, [
            'id_server' => $idServer,
            'name' => $app,
            'version' => $version,
            'configuration' => json_encode(['config_file' => '/etc/' . strtolower($app) . '/main.conf', 'managed_by' => 'ansible']),
            'datetime_recorded' => date('Y-m-d H:i:s', strtotime('-' . ((count($versions) - $i) * 60) . ' days')),
            'id_recorded_by' => $idAdmin,
            'is_current' => $i === count($versions) - 1 ? 1 : 0,
            'note' => $i === 0 ? 'Initial installation.' : 'Upgrade.',
          ]);
        }
      }

      // two weeks of health checks; the last one has the overall status of the server
      if (!$closed) {
        for ($day = 13; $day >= 0; $day--) {
          $isLast = $day === 0;
          $checkOverall = $isLast ? $overall : ((crc32($name . $day) % 9 === 0) ? 2 : 1);
          $metrics = $metricsByOverall[$checkOverall];
          foreach ($metrics as $key => $value) {
            $metrics[$key] = max(1, min(99, $value + (crc32($key . $name . $day) % 7) - 3));
          }
          $metrics['uptime_days'] = 30 + (13 - $day);

          $this->create(HealthStatus::class, [
            'id_server' => $idServer,
            'datetime_checked' => date('Y-m-d H:i:s', strtotime('-' . $day . ' days 08:00')),
            'overall' => $checkOverall,
            'status' => json_encode(array_map('strval', $metrics)),
            'note' => $checkOverall === 4 ? 'Not responding to ping and SSH.' : ($checkOverall === 3 ? 'Disk almost full, investigate.' : ''),
            'id_checked_by' => $idAdmin,
          ]);
        }
      }
    }

    // planned (recurring) activities and performed activities with reports
    /** @var ActivityTemplate $mTemplate */
    $mTemplate = $this->getModel(ActivityTemplate::class);
    $templates = $mTemplate->record->orderBy('id')->get()?->toArray() ?? [];
    $monthly = $templates[0] ?? null;
    $weekly = $templates[1] ?? null;

    /** @var Planner $planner */
    $planner = $this->getService(Planner::class);
    /** @var ServerActivity $mActivity */
    $mActivity = $this->getModel(ServerActivity::class);
    /** @var ActivityReport $mReport */
    $mReport = $this->getModel(ActivityReport::class);

    foreach ($idsServers as $name => $idServer) {
      if ($name === 'legacy-app-01') continue;

      foreach ([[$monthly, 'month', 12, 7], [$weekly, 'week', 26, 2]] as [$template, $period, $count, $inDays]) {
        if (!$template) continue;
        $dateStart = date('Y-m-d', strtotime('+' . $inDays . ' days'));
        $this->create(ServerActivity::class, [
          'subject' => $template['name'] . ' - ' . $name,
          'description' => (string) $template['description'],
          'id_activity_type' => (int) $template['id_activity_type'] ?: null,
          'date_start' => $dateStart,
          'time_start' => (string) $template['default_time'],
          'date_end' => $dateStart,
          'time_end' => date('H:i:s', strtotime((string) $template['default_time'] . ' +' . (int) $template['duration_minutes'] . ' minutes')),
          'all_day' => 0,
          'completed' => 0,
          'recurrence' => json_encode([
            'period' => $period, 'periodEvery' => 1, 'periodCount' => $count,
            'dates' => $planner->expandDates($period, 1, $count, $dateStart),
          ]),
          'id_owner' => $idAdmin,
          'id_server' => $idServer,
          'id_template' => (int) $template['id'],
        ]);
      }
    }

    $performed = [
      ['web-01', 'Monthly system updates', -12, "Took a snapshot.\nInstalled OS updates and nginx 1.26.1.\nRebooted the server.", 'All services are running, no regressions found.', ''],
      ['web-02', 'Weekly manual health check', -5, "Checked CPU, memory, disk.\nReviewed the system log.", 'Memory usage is high (86 %).', 'Investigate the memory usage of php-fpm workers.'],
      ['db-01', 'Weekly manual health check', -3, "Checked disk usage and replication lag.\nChecked the latest backup.", 'Disk usage reached 94 %.', 'Extend the data volume or archive old partitions.'],
      ['db-01', 'Monthly system updates', -25, "Upgraded PostgreSQL from 15.4 to 16.2.\nRan pg_upgrade and vacuumdb.", 'Upgrade finished, queries are about 8 % faster.', ''],
      ['mail-01', 'Monthly security patch review', -9, "Reviewed advisories for Postfix and Dovecot.\nApplied the pending patches.", 'No critical issues.', ''],
    ];

    foreach ($performed as [$serverName, $subject, $daysAgo, $steps, $result, $followup]) {
      $date = date('Y-m-d', strtotime($daysAgo . ' days'));
      $activity = $mActivity->record->recordCreate([
        'subject' => $subject . ' - ' . $serverName,
        'description' => $subject,
        'date_start' => $date,
        'time_start' => '10:00:00',
        'date_end' => $date,
        'time_end' => '11:00:00',
        'all_day' => 0,
        'completed' => 1,
        'id_owner' => $idAdmin,
        'id_server' => $idsServers[$serverName],
      ]);

      $mReport->record->recordCreate([
        'id_activity' => (int) $activity['id'],
        'id_worker' => $idAdmin,
        'steps_performed' => $steps,
        'result' => $result,
        'followup' => $followup,
      ]);
    }
  }

  /**
   * Creates a record and returns its id.
   *
   * @param string $modelClass
   * @param array $data
   *
   * @return int
   *
   */
  private function create(string $modelClass, array $data): int
  {
    $model = $this->getModel($modelClass);
    $record = $model->record->recordCreate($data);
    return (int) $record['id'];
  }
}
