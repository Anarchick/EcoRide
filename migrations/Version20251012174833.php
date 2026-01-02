<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Enum\LuggageSizeEnum;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251012174833 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Convert LuggageSizeEnum string values to ordinals dynamically
        foreach (LuggageSizeEnum::cases() as $case) {
            $this->addSql("UPDATE travel_preferences SET luggage_size = '{$case->ordinal()}' WHERE luggage_size = '{$case->value}'");
        }
        
        // Change column type from VARCHAR to INT
        $this->addSql('ALTER TABLE travel_preferences CHANGE luggage_size luggage_size INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Change column type back to VARCHAR first
        $this->addSql('ALTER TABLE travel_preferences CHANGE luggage_size luggage_size VARCHAR(255) NOT NULL');
        
        // Convert ordinals back to LuggageSizeEnum string values dynamically
        foreach (LuggageSizeEnum::cases() as $case) {
            $this->addSql("UPDATE travel_preferences SET luggage_size = '{$case->value}' WHERE luggage_size = '{$case->ordinal()}'");
        }
    }
}
