<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251225224229 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY `FK_16DB4F891D4EC6B1`');
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY `FK_16DB4F89D4D57CD`');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT FK_16DB4F891D4EC6B1 FOREIGN KEY (testimonial_id) REFERENCES testimonial (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT FK_16DB4F89D4D57CD FOREIGN KEY (staff_id) REFERENCES staff (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE testimonial DROP FOREIGN KEY `FK_E6BDCDF7EE45BDBF`');
        $this->addSql('DROP INDEX UNIQ_E6BDCDF7EE45BDBF ON testimonial');
        $this->addSql('ALTER TABLE testimonial DROP picture_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY FK_16DB4F89D4D57CD');
        $this->addSql('ALTER TABLE picture DROP FOREIGN KEY FK_16DB4F891D4EC6B1');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT `FK_16DB4F89D4D57CD` FOREIGN KEY (staff_id) REFERENCES staff (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE picture ADD CONSTRAINT `FK_16DB4F891D4EC6B1` FOREIGN KEY (testimonial_id) REFERENCES testimonial (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE testimonial ADD picture_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE testimonial ADD CONSTRAINT `FK_E6BDCDF7EE45BDBF` FOREIGN KEY (picture_id) REFERENCES picture (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E6BDCDF7EE45BDBF ON testimonial (picture_id)');
    }
}
