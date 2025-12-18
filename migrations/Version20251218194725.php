<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251218194725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_bans (user_id UUID NOT NULL COMMENT \'(DC2Type:uuid)\', create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', reason LONGTEXT NOT NULL, PRIMARY KEY(user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_bans ADD CONSTRAINT FK_B18D6BE5A76ED395 FOREIGN KEY (user_id) REFERENCES users (uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_bans DROP FOREIGN KEY FK_B18D6BE5A76ED395');
        $this->addSql('DROP TABLE user_bans');
    }
}
