<?php

namespace Migration;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170309230000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE assigner_ipv4_kushang_blacklist_ip (
              id int(10) UNSIGNED NOT NULL,
              ip varchar(32) NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1;');
        $this->addSql('ALTER TABLE assigner_ipv4_kushang_blacklist_ip
          ADD PRIMARY KEY (id),
          ADD UNIQUE KEY ip (ip);');
        $this->addSql('ALTER TABLE assigner_ipv4_kushang_blacklist_ip
          MODIFY id int(10) UNSIGNED NOT NULL AUTO_INCREMENT');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE assigner_ipv4_kushang_blacklist_ip');
    }
}
