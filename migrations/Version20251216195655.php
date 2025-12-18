<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251216195655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reviews ADD moderator_uuid UUID DEFAULT NULL COMMENT \'(DC2Type:uuid)\', CHANGE user_uuid user_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F291AFC9D FOREIGN KEY (moderator_uuid) REFERENCES users (uuid)');
        $this->addSql('CREATE INDEX IDX_6970EB0F291AFC9D ON reviews (moderator_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F291AFC9D');
        $this->addSql('DROP INDEX IDX_6970EB0F291AFC9D ON reviews');
        $this->addSql('ALTER TABLE reviews DROP moderator_uuid, CHANGE user_uuid user_uuid UUID DEFAULT NULL COMMENT \'(DC2Type:uuid)\'');
    }
}
