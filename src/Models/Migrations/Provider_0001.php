<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class Provider_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_providers`;
      create table `servers_providers` (
      `id` int(8) primary key auto_increment,
      `name` varchar(255) ,
      `contact_person` varchar(255) ,
      `email` varchar(255) ,
      `phone` varchar(255) ,
      `website` varchar(255) ,
      `street_line_1` varchar(255) ,
      `street_line_2` varchar(255) ,
      `city` varchar(255) ,
      `postal_code` varchar(255) ,
      `id_country` int(8) NULL default NULL,
      `invoicing_name` varchar(255) ,
      `invoicing_address` text ,
      `tax_id` varchar(255) ,
      `vat_id` varchar(255) ,
      `invoicing_email` varchar(255) ,
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
      drop table if exists `servers_providers`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_providers` ADD CONSTRAINT `fk_1ba06a6e679864fbadade4c8e4a635a9` FOREIGN KEY (`id_country`) REFERENCES `countries` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_providers` DROP FOREIGN KEY `fk_1ba06a6e679864fbadade4c8e4a635a9`;
    ");
  }
}
