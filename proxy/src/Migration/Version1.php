<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version1 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql(file_get_contents(__DIR__ . '/initial_scheme.sql'));
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP VIEW `all_users`, `proxy_package_category`;');
        $this->addSql('
          DROP TABLE `kushang_ips`, `maple_ips`, `proxies_ipv4`, `proxy_countries`, `proxy_packages`, 
            `proxy_pools`, `proxy_regions`, `proxy_server`, `proxy_source`, `proxy_users`, `proxy_user_history`, 
            `proxy_user_packages`, `proxy_user_rebuild`, `reseller_pricing`, `reseller_users`, `reseller_user_packages`, 
            `status`, `user_ips`, `user_ports`, `user_proxy_stats`, `version`;');
    }
}
