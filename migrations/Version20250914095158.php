<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250914095158 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE travels DROP FOREIGN KEY FK_67FF2BD736D5B633');
        $this->addSql('ALTER TABLE travels DROP FOREIGN KEY FK_67FF2BD71A15440A');
        $this->addSql('ALTER TABLE travels ADD CONSTRAINT FK_67FF2BD736D5B633 FOREIGN KEY (driver_uuid) REFERENCES users (uuid) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE travels ADD CONSTRAINT FK_67FF2BD71A15440A FOREIGN KEY (car_uuid) REFERENCES cars (uuid) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE travels DROP FOREIGN KEY FK_67FF2BD736D5B633');
        $this->addSql('ALTER TABLE travels DROP FOREIGN KEY FK_67FF2BD71A15440A');
        $this->addSql('ALTER TABLE travels ADD CONSTRAINT FK_67FF2BD736D5B633 FOREIGN KEY (driver_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE travels ADD CONSTRAINT FK_67FF2BD71A15440A FOREIGN KEY (car_uuid) REFERENCES cars (uuid)');
    }
}
