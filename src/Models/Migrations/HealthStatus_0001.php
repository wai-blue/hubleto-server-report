<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class HealthStatus_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_health_statuses`;
      create table `servers_health_statuses` (
      `id` int(8) primary key auto_increment,
      `id_server` int(8) NULL default NULL,
      `datetime_checked` datetime ,
      `overall` int(255) ,
      `status` text ,
      `note` text ,
      `id_checked_by` int(8) NULL default NULL,
      index `id` (`id`),
      index `id_server` (`id_server`),
      index `datetime_checked` (`datetime_checked`),
      index `overall` (`overall`),
      index `id_checked_by` (`id_checked_by`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_health_statuses`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_health_statuses` ADD CONSTRAINT `fk_e7bdd7f1ee3d124b4350067adfbf6dc8` FOREIGN KEY (`id_server`) REFERENCES `servers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
      ALTER TABLE `servers_health_statuses` ADD CONSTRAINT `fk_fe7f63206728532fcdfc640ff6bc1c06` FOREIGN KEY (`id_checked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_health_statuses` DROP FOREIGN KEY `fk_e7bdd7f1ee3d124b4350067adfbf6dc8`;
      ALTER TABLE `servers_health_statuses` DROP FOREIGN KEY `fk_fe7f63206728532fcdfc640ff6bc1c06`;
    ");
  }
}
