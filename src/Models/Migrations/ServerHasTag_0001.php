<?php

namespace Hubleto\App\External\WaiBlue\ServerReport\Models\Migrations;

class ServerHasTag_0001 extends \Hubleto\Framework\Migration
{
  public function upgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_has_tags`;
      create table `servers_has_tags` (
      `id` int(8) primary key auto_increment,
      `id_server` int(8) NULL default NULL,
      `id_tag` int(8) NULL default NULL,
      index `id` (`id`),
      index `id_server` (`id_server`),
      index `id_tag` (`id_tag`)
      ) ENGINE = InnoDB;
      set foreign_key_checks = 1;
    ");
  }

  public function downgradeSchema(): void
  {
    $this->db->execute("
      set foreign_key_checks = 0;
      drop table if exists `servers_has_tags`;
      set foreign_key_checks = 1;
    ");
  }

  public function upgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_has_tags` ADD CONSTRAINT `fk_e00a1cc90027502a5f0bd139b1e0aa4c` FOREIGN KEY (`id_server`) REFERENCES `servers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
      ALTER TABLE `servers_has_tags` ADD CONSTRAINT `fk_6b9ff219ce1c67f858ffd33744c0ad76` FOREIGN KEY (`id_tag`) REFERENCES `servers_tags` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT;
    ");
  }

  public function downgradeForeignKeys(): void
  {
    $this->db->execute("
      ALTER TABLE `servers_has_tags` DROP FOREIGN KEY `fk_e00a1cc90027502a5f0bd139b1e0aa4c`;
      ALTER TABLE `servers_has_tags` DROP FOREIGN KEY `fk_6b9ff219ce1c67f858ffd33744c0ad76`;
    ");
  }
}
