<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20171122111502 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_mta_exclude ADD user_key VARCHAR(64) DEFAULT NULL AFTER `user_id`, CHANGE user_id user_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user_mta_ips ADD user_key VARCHAR(64) DEFAULT NULL AFTER `user_id`, CHANGE user_id user_id INT UNSIGNED DEFAULT NULL');
        $this->addSql('ALTER TABLE user_mta_ips DROP INDEX user_id');
        $this->addSql('ALTER TABLE user_mta_otp ADD user_key VARCHAR(64) DEFAULT NULL AFTER `user_id`, CHANGE user_id user_id INT UNSIGNED DEFAULT NULL, CHANGE attempts attempts SMALLINT UNSIGNED NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_mta_exclude DROP user_key, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_mta_ips DROP user_key, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX user_id ON user_mta_ips (user_id, ip)');
        $this->addSql('ALTER TABLE user_mta_otp DROP user_key, CHANGE user_id user_id INT NOT NULL');
    }
}
