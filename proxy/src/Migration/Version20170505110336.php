<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170505110336 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE reseller_tracked_packages (id INT UNSIGNED AUTO_INCREMENT NOT NULL, package_id INT UNSIGNED NOT NULL, reseller_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NOT NULL, country VARCHAR(32) NOT NULL COLLATE utf8_general_ci, category VARCHAR(32) NOT NULL COLLATE utf8_general_ci, ports SMALLINT UNSIGNED NOT NULL, date_added DATETIME NOT NULL, date_charged DATETIME NOT NULL, date_charged_until DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE resellers CHANGE credits credits DOUBLE PRECISION DEFAULT \'0.0000\' NOT NULL');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE reseller_tracked_packages');
        $this->addSql('ALTER TABLE resellers CHANGE credits credits NUMERIC(10, 2) DEFAULT NULL');
    }
}
