<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251218180151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE platform_commissions (id INT AUTO_INCREMENT NOT NULL, travel UUID NOT NULL COMMENT \'(DC2Type:uuid)\', carpooler INT NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', credits INT NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_3105756B2D0B6BCE (travel), UNIQUE INDEX UNIQ_3105756B70187F56 (carpooler), UNIQUE INDEX unique_carpooler_per_travel (carpooler, travel), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE platform_commissions ADD CONSTRAINT FK_3105756B2D0B6BCE FOREIGN KEY (travel) REFERENCES travels (uuid)');
        $this->addSql('ALTER TABLE platform_commissions ADD CONSTRAINT FK_3105756B70187F56 FOREIGN KEY (carpooler) REFERENCES Carpoolers (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE platform_commissions DROP FOREIGN KEY FK_3105756B2D0B6BCE');
        $this->addSql('ALTER TABLE platform_commissions DROP FOREIGN KEY FK_3105756B70187F56');
        $this->addSql('DROP TABLE platform_commissions');
    }
}
