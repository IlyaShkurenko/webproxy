<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170429170846 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE resellers (id INT UNSIGNED AUTO_INCREMENT NOT NULL, email VARCHAR(255) DEFAULT NULL COLLATE latin1_swedish_ci, api_key VARCHAR(50) DEFAULT NULL COLLATE latin1_swedish_ci, credits NUMERIC(10, 2) DEFAULT NULL, UNIQUE INDEX api_key_UNIQUE (api_key), UNIQUE INDEX email_UNIQUE (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('INSERT INTO `resellers` (`id`, `email`, `api_key`, `credits`) VALUES (1, \'admin@blazingseollc.com\', \'NeilsKeyfbH6IgtotGdxbKD1\', \'99999999.00\');');
        $this->addSql('ALTER TABLE proxy_users ADD reseller_id INT UNSIGNED DEFAULT 1 NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE resellers');
        $this->addSql('ALTER TABLE proxy_users DROP reseller_id');
    }
}
