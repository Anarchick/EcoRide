<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251016203353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Carpoolers (id INT AUTO_INCREMENT NOT NULL, travel_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', user_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', slots SMALLINT NOT NULL, cost INT NOT NULL, booked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D62B20651A27B30B (travel_uuid), INDEX IDX_D62B2065ABFE1C6F (user_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE Carpoolers ADD CONSTRAINT FK_D62B20651A27B30B FOREIGN KEY (travel_uuid) REFERENCES travels (uuid)');
        $this->addSql('ALTER TABLE Carpoolers ADD CONSTRAINT FK_D62B2065ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE user_travel DROP FOREIGN KEY FK_485970F31A27B30B');
        $this->addSql('ALTER TABLE user_travel DROP FOREIGN KEY FK_485970F3ABFE1C6F');
        $this->addSql('DROP TABLE user_travel');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_travel (user_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', travel_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_485970F31A27B30B (travel_uuid), INDEX IDX_485970F3ABFE1C6F (user_uuid), PRIMARY KEY(user_uuid, travel_uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_travel ADD CONSTRAINT FK_485970F31A27B30B FOREIGN KEY (travel_uuid) REFERENCES travels (uuid)');
        $this->addSql('ALTER TABLE user_travel ADD CONSTRAINT FK_485970F3ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE Carpoolers DROP FOREIGN KEY FK_D62B20651A27B30B');
        $this->addSql('ALTER TABLE Carpoolers DROP FOREIGN KEY FK_D62B2065ABFE1C6F');
        $this->addSql('DROP TABLE Carpoolers');
    }
}
