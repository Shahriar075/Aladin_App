<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240612061908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendance CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE `leave` CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE role CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE team CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE team_lead team_lead INT NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649296CD8AE FOREIGN KEY (team_id) REFERENCES team (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649296CD8AE ON user (team_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE attendance CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE `leave` CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE role CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE team CHANGE created_at created_at DATETIME NOT NULL, CHANGE team_lead team_lead VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649296CD8AE');
        $this->addSql('DROP INDEX IDX_8D93D649296CD8AE ON user');
    }
}
