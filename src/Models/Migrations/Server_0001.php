<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class Server_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers`;
      create table `servers` (
      `id` int(8) primary key auto_increment,
      `name` varchar(255) ,
      `description` text ,
      `connection_details` text ,
      `doc_url_1` varchar(255) ,
      `doc_url_2` varchar(255) ,
      `doc_url_3` varchar(255) ,
      `is_closed` int(1) ,
      `id_owner` int(8) NULL default NULL,
      `id_manager` int(8) NULL default NULL,
      `shared_with` text ,
      `id_customer` int(8) NULL default NULL,
      `id_datacenter` int(8) NULL default NULL,
      `room` varchar(255) ,
      `rack` varchar(255) ,
      `rack_position` varchar(255) ,
      `id_provider` int(8) NULL default NULL,
      `id_workflow` int(8) NULL default NULL,
      `id_workflow_step` int(8) NULL default NULL,
      `note` text ,
      `id_template` int(8) NULL default NULL,
      `id_document` int(8) NULL default NULL,
      `pdf` varchar(255) ,
      index `id` (`id`),
      index `is_closed` (`is_closed`),
      index `id_owner` (`id_owner`),
      index `id_manager` (`id_manager`),
      index `id_customer` (`id_customer`),
      index `id_datacenter` (`id_datacenter`),
      index `id_provider` (`id_provider`),
      index `id_workflow` (`id_workflow`),
      index `id_workflow_step` (`id_workflow_step`),
      index `id_template` (`id_template`),
      index `id_document` (`id_document`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers` ADD CONSTRAINT `fk_d8efdd42a560783b3d717c525a8d4366` FOREIGN KEY (`id_owner`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_9c92d3daa3e1133ae42057f56dcf29a8` FOREIGN KEY (`id_manager`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_f20288f0a9b6440fe9317d76fd2e7dfc` FOREIGN KEY (`id_customer`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_29b47080fede9919d2a859e1e9ca2b95` FOREIGN KEY (`id_datacenter`) REFERENCES `servers_datacenters` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_cf80443132ee507013c2e84d2f532d16` FOREIGN KEY (`id_provider`) REFERENCES `servers_providers` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_140c9a2ea9898db677e6964f942ce798` FOREIGN KEY (`id_workflow`) REFERENCES `workflows` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_39f4480f6fbb8a11ff421defacd09512` FOREIGN KEY (`id_workflow_step`) REFERENCES `workflow_steps` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_9c7be90979f5ca6cdc4c669aad9aef4e` FOREIGN KEY (`id_template`) REFERENCES `documents_templates` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
      ALTER TABLE `servers` ADD CONSTRAINT `fk_5f8932b16785f6abb2c32442ec4a7717` FOREIGN KEY (`id_document`) REFERENCES `documents` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_d8efdd42a560783b3d717c525a8d4366`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_9c92d3daa3e1133ae42057f56dcf29a8`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_f20288f0a9b6440fe9317d76fd2e7dfc`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_29b47080fede9919d2a859e1e9ca2b95`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_cf80443132ee507013c2e84d2f532d16`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_140c9a2ea9898db677e6964f942ce798`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_39f4480f6fbb8a11ff421defacd09512`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_9c7be90979f5ca6cdc4c669aad9aef4e`;
      ALTER TABLE `servers` DROP FOREIGN KEY `fk_5f8932b16785f6abb2c32442ec4a7717`;
    ");
  }
}
