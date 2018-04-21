<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171101152739 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE `proxies_ipv6`(
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `source_id` INT UNSIGNED NOT NULL,
          `block` VARCHAR(39) NOT NULL,
          `subnet` TINYINT NOT NULL DEFAULT \'48\',
          `location_id` INT UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL,
          `updated_at` DATETIME NOT NULL,
          PRIMARY KEY(`id`),
          UNIQUE(`block`)
        ) ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE `proxies_ipv6_sources`(
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `block` VARCHAR(39) NOT NULL,
          `subnet` TINYINT NOT NULL DEFAULT \'32\',
          `server_id` INT UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL,
          `updated_at` DATETIME NOT NULL,
          PRIMARY KEY(`id`),
          UNIQUE(`block`)
        ) ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE `proxy_servers_ipv6`(
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `ip` VARCHAR(15) NOT NULL,
          `name` VARCHAR(64) NOT NULL,
          `created_at` DATETIME NOT NULL,
          `updated_at` DATETIME NOT NULL,
          PRIMARY KEY(`id`),
          UNIQUE(`ip`),
          UNIQUE(`name`)
        ) ENGINE = InnoDB;');
        $this->addSql('CREATE TABLE `user_ports_ipv6`(
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `package_id` INT UNSIGNED NOT NULL,
          `block_id` INT UNSIGNED NULL,
          `user_id` INT UNSIGNED NOT NULL,
          `created_at` DATETIME NOT NULL,
          `assigned_at` DATETIME NULL,
          `updated_at` DATETIME NOT NULL,
          PRIMARY KEY(`id`)
        ) ENGINE = InnoDB;');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD `ip_v` SET("4", "6") DEFAULT "4" NOT NULL AFTER `ports`;');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD `type` VARCHAR(32) DEFAULT "single" NOT NULL AFTER `ip_v`;');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD `ext` VARCHAR(128) NULL AFTER `category`;');
        $this->addSql('UPDATE `proxy_user_packages` SET `ip_v` = "4", `type` = "single";');
        $this->addSql('ALTER TABLE proxy_user_packages DROP INDEX UNIQUE_COUNTRY;');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD INDEX `ip_v` ( `ip_v`);');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD INDEX `type_country_category` ( `type`, `country`, `category`);');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD INDEX `ipv_type_country_category` ( `ip_v`, `type`, `country`, `category`);');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD INDEX `status` ( `status`);');
        $this->addSql('ALTER TABLE `proxy_user_packages` ADD INDEX `user_id` ( `user_id`);');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE proxies_ipv6');
        $this->addSql('DROP TABLE proxies_ipv6_sources');
        $this->addSql('DROP TABLE proxy_servers_ipv6');
        $this->addSql('DROP TABLE user_ports_ipv6');
        $this->addSql('DROP INDEX ip_v ON proxy_user_packages');
        $this->addSql('DROP INDEX type_country_category ON proxy_user_packages');
        $this->addSql('DROP INDEX ipv_type_country_category ON proxy_user_packages');
        $this->addSql('DROP INDEX status ON proxy_user_packages');
        $this->addSql('DROP INDEX user_id ON proxy_user_packages');
        $this->addSql('ALTER TABLE proxy_user_packages DROP ip_v, DROP `type`, DROP ext');
        $this->addSql('CREATE UNIQUE INDEX UNIQUE_COUNTRY ON proxy_user_packages (user_id, country, category)');
    }
}
