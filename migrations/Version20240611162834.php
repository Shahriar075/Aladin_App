<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240611162834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `leave` ADD approved_by_id INT DEFAULT NULL, DROP approved_by');
        $this->addSql('ALTER TABLE `leave` ADD CONSTRAINT FK_9BB080D02D234F6A FOREIGN KEY (approved_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_9BB080D02D234F6A ON `leave` (approved_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `leave` DROP FOREIGN KEY FK_9BB080D02D234F6A');
        $this->addSql('DROP INDEX IDX_9BB080D02D234F6A ON `leave`');
        $this->addSql('ALTER TABLE `leave` ADD approved_by VARCHAR(255) NOT NULL, DROP approved_by_id');
    }
}
