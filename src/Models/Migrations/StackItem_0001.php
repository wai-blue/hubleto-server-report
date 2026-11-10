<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class StackItem_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_stack_items`;
      create table `servers_stack_items` (
      `id` int(8) primary key auto_increment,
      `id_server` int(8) NULL default NULL,
      `name` varchar(255) ,
      `version` varchar(255) ,
      `configuration` text ,
      `datetime_recorded` datetime ,
      `id_recorded_by` int(8) NULL default NULL,
      `is_current` int(1) ,
      `note` text ,
      index `id` (`id`),
      index `id_server` (`id_server`),
      index `datetime_recorded` (`datetime_recorded`),
      index `id_recorded_by` (`id_recorded_by`),
      index `is_current` (`is_current`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_stack_items`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_stack_items` ADD CONSTRAINT `fk_4909460d4ae592d28220e992eb17ce5f` FOREIGN KEY (`id_server`) REFERENCES `servers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
      ALTER TABLE `servers_stack_items` ADD CONSTRAINT `fk_ba7757f2da34ceacf225ad183c543a27` FOREIGN KEY (`id_recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_stack_items` DROP FOREIGN KEY `fk_4909460d4ae592d28220e992eb17ce5f`;
      ALTER TABLE `servers_stack_items` DROP FOREIGN KEY `fk_ba7757f2da34ceacf225ad183c543a27`;
    ");
  }
}
