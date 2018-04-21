<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170929090345 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE proxy_users DROP INDEX email, ADD UNIQUE email (email, reseller_id) USING BTREE');
        $this->addSql('ALTER TABLE proxy_users DROP INDEX whmcs_id, ADD UNIQUE whmcs_id (whmcs_id, reseller_id) USING BTREE');
        $this->addSql('ALTER TABLE proxy_users DROP INDEX amember_id, ADD UNIQUE amember_id (amember_id, reseller_id) USING BTREE');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE proxy_users DROP INDEX email, ADD UNIQUE email (email) USING BTREE');
        $this->addSql('ALTER TABLE proxy_users DROP INDEX whmcs_id, ADD UNIQUE whmcs_id (whmcs_id) USING BTREE');
        $this->addSql('ALTER TABLE proxy_users DROP INDEX amember_id, ADD UNIQUE amember_id (amember_id) USING BTREE');
    }
}
