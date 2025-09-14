<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250908212536 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_E4D630095E237E06 ON models');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_MODEL_BRAND_NAME ON models (brand_id, name)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_MODEL_BRAND_NAME ON models');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E4D630095E237E06 ON models (name)');
    }
}
