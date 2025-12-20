<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251220210801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY `FK_16DB4F89D4D57CD`');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT FK_16DB4F89D4D57CD FOREIGN KEY (staff_id) REFERENCES staff (id)');
        $this->addSql('ALTER TABLE staff CHANGE is_active is_active TINYINT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY FK_16DB4F89D4D57CD');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT `FK_16DB4F89D4D57CD` FOREIGN KEY (staff_id) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE staff CHANGE is_active is_active TINYINT NOT NULL');
    }
}
