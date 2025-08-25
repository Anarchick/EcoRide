<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250821125908 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cars CHANGE plate plate VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE last_name last_name VARCHAR(255) NOT NULL, CHANGE email email VARCHAR(255) NOT NULL, CHANGE phone phone VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users CHANGE last_name last_name VARCHAR(25) NOT NULL, CHANGE email email VARCHAR(128) NOT NULL, CHANGE phone phone VARCHAR(15) NOT NULL');
        $this->addSql('ALTER TABLE cars CHANGE plate plate VARCHAR(10) NOT NULL');
    }
}
