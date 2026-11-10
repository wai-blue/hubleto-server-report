<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class ActivityTemplate_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_activity_templates`;
      create table `servers_activity_templates` (
      `id` int(8) primary key auto_increment,
      `name` varchar(255) ,
      `id_activity_type` int(8) NULL default NULL,
      `description` text ,
      `period` int(255) ,
      `period_every` int(255) ,
      `period_count` int(255) ,
      `duration_minutes` int(255) ,
      `default_time` time ,
      `is_active` int(1) ,
      index `id` (`id`),
      index `id_activity_type` (`id_activity_type`),
      index `period` (`period`),
      index `period_every` (`period_every`),
      index `period_count` (`period_count`),
      index `duration_minutes` (`duration_minutes`),
      index `default_time` (`default_time`),
      index `is_active` (`is_active`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_activity_templates`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_activity_templates` ADD CONSTRAINT `fk_2fbcc9dcc0f56d829152cff2a8e4be40` FOREIGN KEY (`id_activity_type`) REFERENCES `activity_types` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_activity_templates` DROP FOREIGN KEY `fk_2fbcc9dcc0f56d829152cff2a8e4be40`;
    ");
  }
}
