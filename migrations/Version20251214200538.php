<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251214200538 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // Step 1: Clean orphaned travel_uuid references
        $this->addSql('DELETE FROM reviews WHERE travel_uuid IS NOT NULL AND travel_uuid NOT IN (SELECT uuid FROM travels)');
        
        // Step 2: Add foreign key constraint
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F1A27B30B FOREIGN KEY (travel_uuid) REFERENCES travels (uuid)');
        $this->addSql('CREATE INDEX IDX_6970EB0F1A27B30B ON reviews (travel_uuid)');
        $this->addSql('CREATE UNIQUE INDEX unique_review_per_travel ON reviews (author_uuid, user_uuid, travel_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F1A27B30B');
        $this->addSql('DROP INDEX IDX_6970EB0F1A27B30B ON reviews');
        $this->addSql('DROP INDEX unique_review_per_travel ON reviews');
    }
}
