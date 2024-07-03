<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240615085648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team ADD team_lead VARCHAR(255) NOT NULL, DROP created_at, DROP updated_at, DROP created_by, DROP updated_by');
        $this->addSql('ALTER TABLE user ADD team_lead_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649FF2C34BA FOREIGN KEY (team_lead_id) REFERENCES team (id)');
        $this->addSql('CREATE INDEX IDX_8D93D649FF2C34BA ON user (team_lead_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL, ADD created_by VARCHAR(255) DEFAULT NULL, ADD updated_by VARCHAR(255) DEFAULT NULL, DROP team_lead');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649FF2C34BA');
        $this->addSql('DROP INDEX IDX_8D93D649FF2C34BA ON user');
        $this->addSql('ALTER TABLE user DROP team_lead_id');
    }
}