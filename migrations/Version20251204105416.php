<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204105416 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        
        // First, populate plate_hash for existing records
        $this->addSql('UPDATE cars SET plate_hash = SHA2(UPPER(plate), 256) WHERE plate_hash IS NULL OR plate_hash = \'\'');
        
        // Then create the unique index
        $this->addSql('CREATE UNIQUE INDEX UNIQ_95C71D14B974B014 ON cars (plate_hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_95C71D14B974B014 ON cars');
    }
}
