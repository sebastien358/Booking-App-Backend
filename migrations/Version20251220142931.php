<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220142931 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE staff (id INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(125) NOT NULL, lastname VARCHAR(125) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE appointment ADD staff_id INT NOT NULL, CHANGE service_id service_id INT NOT NULL');
        $this->addSql('ALTER TABLE appointment ADD CONSTRAINT FK_FE38F844D4D57CD FOREIGN KEY (staff_id) REFERENCES staff (id)');
        $this->addSql('CREATE INDEX IDX_FE38F844D4D57CD ON appointment (staff_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE staff');
        $this->addSql('ALTER TABLE appointment DROP FOREIGN KEY FK_FE38F844D4D57CD');
        $this->addSql('DROP INDEX IDX_FE38F844D4D57CD ON appointment');
        $this->addSql('ALTER TABLE appointment DROP staff_id, CHANGE service_id service_id INT DEFAULT NULL');
    }
}
