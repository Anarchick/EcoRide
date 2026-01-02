<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251205094017 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert TravelStateEnum from string to int type';
    }

    public function up(Schema $schema): void
    {
        // Add temporary column to store converted values
        $this->addSql('ALTER TABLE travels ADD state_temp INT DEFAULT NULL');
        
        // Convert existing string values to int values
        // CANCELLED = 'cancelled' -> 0
        $this->addSql("UPDATE travels SET state_temp = 0 WHERE state = 'cancelled'");
        // COMPLETED = 'completed' -> 1
        $this->addSql("UPDATE travels SET state_temp = 1 WHERE state = 'completed'");
        // PENDING = 'pending' -> 2
        $this->addSql("UPDATE travels SET state_temp = 2 WHERE state = 'pending'");
        // FULL = 'full' -> 3
        $this->addSql("UPDATE travels SET state_temp = 3 WHERE state = 'full'");
        // IN_PROGRESS = 'in_progress' -> 4
        $this->addSql("UPDATE travels SET state_temp = 4 WHERE state = 'in_progress'");
        
        // Drop old column
        $this->addSql('ALTER TABLE travels DROP state');
        
        // Rename temp column to state
        $this->addSql('ALTER TABLE travels CHANGE state_temp state INT NOT NULL');
        
        // Recreate index on state column
        $this->addSql('CREATE INDEX IDX_67FF2BD7A393D2FB ON travels (state)');
    }

    public function down(Schema $schema): void
    {
        // Add temporary column to store converted values
        $this->addSql('ALTER TABLE travels ADD state_temp VARCHAR(255) DEFAULT NULL');
        
        // Convert int values back to string values
        // 0 -> 'cancelled'
        $this->addSql("UPDATE travels SET state_temp = 'cancelled' WHERE state = 0");
        // 1 -> 'completed'
        $this->addSql("UPDATE travels SET state_temp = 'completed' WHERE state = 1");
        // 2 -> 'pending'
        $this->addSql("UPDATE travels SET state_temp = 'pending' WHERE state = 2");
        // 3 -> 'full'
        $this->addSql("UPDATE travels SET state_temp = 'full' WHERE state = 3");
        // 4 -> 'in_progress'
        $this->addSql("UPDATE travels SET state_temp = 'in_progress' WHERE state = 4");
        
        // Drop old column
        $this->addSql('ALTER TABLE travels DROP state');
        
        // Rename temp column to state
        $this->addSql('ALTER TABLE travels CHANGE state_temp state VARCHAR(255) NOT NULL');
        
        // Recreate index on state column
        $this->addSql('CREATE INDEX IDX_67FF2BD7A393D2FB ON travels (state)');
    }
}
