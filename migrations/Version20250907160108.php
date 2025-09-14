<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250907160108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_67FF2BD73EC63EAA ON travels');
        $this->addSql('DROP INDEX idx_search_criteria ON travels');
        $this->addSql('ALTER TABLE travels ADD departure VARCHAR(90) NOT NULL, ADD arrival VARCHAR(90) NOT NULL, DROP origin, DROP destination');
        $this->addSql('CREATE INDEX IDX_67FF2BD75BE55CB4 ON travels (arrival)');
        $this->addSql('CREATE INDEX idx_search_criteria ON travels (departure, arrival, date, passengers_max)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_67FF2BD75BE55CB4 ON travels');
        $this->addSql('DROP INDEX idx_search_criteria ON travels');
        $this->addSql('ALTER TABLE travels ADD origin VARCHAR(90) NOT NULL, ADD destination VARCHAR(90) NOT NULL, DROP departure, DROP arrival');
        $this->addSql('CREATE INDEX IDX_67FF2BD73EC63EAA ON travels (destination)');
        $this->addSql('CREATE INDEX idx_search_criteria ON travels (origin, destination, date, passengers_max)');
    }
}
