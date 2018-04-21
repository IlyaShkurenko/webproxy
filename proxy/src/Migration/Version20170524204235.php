<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170524204235 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("ALTER TABLE `proxy_user_packages` ADD `status` SET('active','suspended') NOT NULL DEFAULT 'active' AFTER `category`;");
        $this->addSql("CREATE TABLE user_ports_frozen (
            id int(11) NOT NULL,
            package_id int(11) NOT NULL,
            proxy_id int(11) NOT NULL,
            user_id int(11) NOT NULL,
            user_type varchar(3) NOT NULL,
            port_data mediumtext NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
        $this->addSql("ALTER TABLE user_ports_frozen
            ADD PRIMARY KEY (id),
            ADD KEY proxy_id (proxy_id),
            ADD KEY user_id (user_id,user_type,proxy_id) USING BTREE,
            ADD KEY user_id_2 (user_id,user_type) USING BTREE;");
        $this->addSql("ALTER TABLE user_ports_frozen MODIFY id int(11) NOT NULL AUTO_INCREMENT;");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE proxy_user_packages DROP status');
        $this->addSql('DROP TABLE user_ports_frozen');
    }
}
