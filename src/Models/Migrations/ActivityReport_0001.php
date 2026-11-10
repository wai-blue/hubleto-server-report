<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class ActivityReport_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_activity_reports`;
      create table `servers_activity_reports` (
      `id` int(8) primary key auto_increment,
      `id_activity` int(8) NULL default NULL,
      `activity_subject` varchar(255) ,
      `activity_description` text ,
      `activity_date` date ,
      `activity_time` time ,
      `id_server` int(8) NULL default NULL,
      `server_name` varchar(255) ,
      `id_worker` int(8) NULL default NULL,
      `worker_name` varchar(255) ,
      `steps_performed` text ,
      `result` text ,
      `followup` text ,
      index `id` (`id`),
      index `id_activity` (`id_activity`),
      index `activity_date` (`activity_date`),
      index `activity_time` (`activity_time`),
      index `id_server` (`id_server`),
      index `id_worker` (`id_worker`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_activity_reports`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_activity_reports` ADD CONSTRAINT `fk_45883277fcc117dc098d4efbec817dd7` FOREIGN KEY (`id_activity`) REFERENCES `servers_activities` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers_activity_reports` ADD CONSTRAINT `fk_ba0050ade7530c408c0a5c368c9f7bed` FOREIGN KEY (`id_server`) REFERENCES `servers` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers_activity_reports` ADD CONSTRAINT `fk_14d31d06538fadec3d367dd5009e684a` FOREIGN KEY (`id_worker`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_activity_reports` DROP FOREIGN KEY `fk_45883277fcc117dc098d4efbec817dd7`;
      ALTER TABLE `servers_activity_reports` DROP FOREIGN KEY `fk_ba0050ade7530c408c0a5c368c9f7bed`;
      ALTER TABLE `servers_activity_reports` DROP FOREIGN KEY `fk_14d31d06538fadec3d367dd5009e684a`;
    ");
  }
}
