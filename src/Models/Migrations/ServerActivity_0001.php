<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class ServerActivity_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_activities`;
      create table `servers_activities` (
      `id` int(8) primary key auto_increment,
      `subject` varchar(255) ,
      `location` varchar(255) ,
      `online_meeting_link` varchar(255) ,
      `description` text ,
      `id_activity_type` int(8) NULL default NULL,
      `date_start` date ,
      `time_start` time ,
      `date_end` date ,
      `time_end` time ,
      `recurrence` text ,
      `all_day` int(1) ,
      `completed` int(1) ,
      `meeting_minutes_link` varchar(255) ,
      `meeting_minutes` text ,
      `id_owner` int(8) NULL default NULL,
      `id_server` int(8) NULL default NULL,
      `id_template` int(8) NULL default NULL,
      index `id` (`id`),
      index `id_activity_type` (`id_activity_type`),
      index `date_start` (`date_start`),
      index `time_start` (`time_start`),
      index `date_end` (`date_end`),
      index `time_end` (`time_end`),
      index `all_day` (`all_day`),
      index `completed` (`completed`),
      index `id_owner` (`id_owner`),
      index `id_server` (`id_server`),
      index `id_template` (`id_template`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_activities`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_activities` ADD CONSTRAINT `fk_3a55e67cd28ed35f59a222bd26e7e68a` FOREIGN KEY (`id_activity_type`) REFERENCES `activity_types` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers_activities` ADD CONSTRAINT `fk_80b733bda0171f643ab1509b9b6c6072` FOREIGN KEY (`id_owner`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT;
      ALTER TABLE `servers_activities` ADD CONSTRAINT `fk_f4e65ac56d0921e3a203fda3852a6f3f` FOREIGN KEY (`id_server`) REFERENCES `servers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
      ALTER TABLE `servers_activities` ADD CONSTRAINT `fk_ec425e13545c8d1ac6b0e1fc31f35895` FOREIGN KEY (`id_template`) REFERENCES `servers_activity_templates` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_activities` DROP FOREIGN KEY `fk_3a55e67cd28ed35f59a222bd26e7e68a`;
      ALTER TABLE `servers_activities` DROP FOREIGN KEY `fk_80b733bda0171f643ab1509b9b6c6072`;
      ALTER TABLE `servers_activities` DROP FOREIGN KEY `fk_f4e65ac56d0921e3a203fda3852a6f3f`;
      ALTER TABLE `servers_activities` DROP FOREIGN KEY `fk_ec425e13545c8d1ac6b0e1fc31f35895`;
    ");
  }
}
