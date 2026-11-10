<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class Datacenter_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_datacenters`;
      create table `servers_datacenters` (
      `id` int(8) primary key auto_increment,
      `name` varchar(255) ,
      `street_line_1` varchar(255) ,
      `street_line_2` varchar(255) ,
      `city` varchar(255) ,
      `postal_code` varchar(255) ,
      `id_country` int(8) NULL default NULL,
      `access_info` text ,
      `description` text ,
      `note` text ,
      index `id` (`id`),
      index `id_country` (`id_country`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_datacenters`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_datacenters` ADD CONSTRAINT `fk_a27982aa9978871b03da2b25979ff420` FOREIGN KEY (`id_country`) REFERENCES `countries` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_datacenters` DROP FOREIGN KEY `fk_a27982aa9978871b03da2b25979ff420`;
    ");
  }
}
