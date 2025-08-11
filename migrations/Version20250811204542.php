<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250811204542 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE brands (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_7EA244345E237E06 (name), INDEX IDX_7EA244345E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cars (uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', brand_id INT NOT NULL, model_id INT NOT NULL, plate VARCHAR(10) NOT NULL, fuel_type VARCHAR(255) NOT NULL, color VARCHAR(255) NOT NULL, total_seats SMALLINT NOT NULL, UNIQUE INDEX UNIQ_95C71D14719ED75B (plate), INDEX IDX_95C71D1444F5D008 (brand_id), INDEX IDX_95C71D147975B7E7 (model_id), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE car_user (car_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', user_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_46F9C2E51A15440A (car_uuid), INDEX IDX_46F9C2E5ABFE1C6F (user_uuid), PRIMARY KEY(car_uuid, user_uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE models (id INT AUTO_INCREMENT NOT NULL, brand_id INT NOT NULL, name VARCHAR(100) NOT NULL, UNIQUE INDEX UNIQ_E4D630095E237E06 (name), INDEX IDX_E4D630095E237E06 (name), INDEX IDX_E4D6300944F5D008 (brand_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reviews (id INT AUTO_INCREMENT NOT NULL, author_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', user_uuid UUID DEFAULT NULL COMMENT \'(DC2Type:uuid)\', rate SMALLINT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', content LONGTEXT DEFAULT NULL, INDEX IDX_6970EB0F3590D879 (author_uuid), INDEX IDX_6970EB0FABFE1C6F (user_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE transactions (id INT AUTO_INCREMENT NOT NULL, user_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', credits INT NOT NULL, currency VARCHAR(255) NOT NULL, price INT NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', comment LONGTEXT DEFAULT NULL, INDEX IDX_EAA81A4CABFE1C6F (user_uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE travel_preferences (travel_id UUID NOT NULL COMMENT \'(DC2Type:uuid)\', is_smoking_allowed TINYINT(1) NOT NULL, is_pets_allowed TINYINT(1) NOT NULL, luggage_size VARCHAR(255) NOT NULL, comment LONGTEXT DEFAULT NULL, INDEX IDX_F6B2907A6B238DF5 (is_smoking_allowed), INDEX IDX_F6B2907A8AB314B8 (is_pets_allowed), INDEX IDX_F6B2907AB46F05F8 (luggage_size), PRIMARY KEY(travel_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE travels (uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', driver_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', car_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', origin VARCHAR(90) NOT NULL, destination VARCHAR(90) NOT NULL, date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', duration INT NOT NULL, distance INT NOT NULL, passengers_max SMALLINT NOT NULL, cost INT NOT NULL, state VARCHAR(255) NOT NULL, INDEX IDX_67FF2BD73EC63EAA (destination), INDEX IDX_67FF2BD7AA9E377A (date), INDEX IDX_67FF2BD7A393D2FB (state), INDEX IDX_67FF2BD736D5B633 (driver_uuid), INDEX IDX_67FF2BD71A15440A (car_uuid), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', roles LONGTEXT NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', name VARCHAR(20) NOT NULL, last_name VARCHAR(25) NOT NULL, username VARCHAR(20) NOT NULL, email VARCHAR(128) NOT NULL, phone VARCHAR(15) NOT NULL, credits INT NOT NULL, bio LONGTEXT DEFAULT NULL, is_verified TINYINT(1) NOT NULL, rating_average NUMERIC(3, 2) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9444F97DD (phone), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_travel (user_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', travel_uuid UUID NOT NULL COMMENT \'(DC2Type:uuid)\', INDEX IDX_485970F3ABFE1C6F (user_uuid), INDEX IDX_485970F31A27B30B (travel_uuid), PRIMARY KEY(user_uuid, travel_uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cars ADD CONSTRAINT FK_95C71D1444F5D008 FOREIGN KEY (brand_id) REFERENCES brands (id)');
        $this->addSql('ALTER TABLE cars ADD CONSTRAINT FK_95C71D147975B7E7 FOREIGN KEY (model_id) REFERENCES models (id)');
        $this->addSql('ALTER TABLE car_user ADD CONSTRAINT FK_46F9C2E51A15440A FOREIGN KEY (car_uuid) REFERENCES cars (uuid)');
        $this->addSql('ALTER TABLE car_user ADD CONSTRAINT FK_46F9C2E5ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE models ADD CONSTRAINT FK_E4D6300944F5D008 FOREIGN KEY (brand_id) REFERENCES brands (id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F3590D879 FOREIGN KEY (author_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0FABFE1C6F FOREIGN KEY (user_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE transactions ADD CONSTRAINT FK_EAA81A4CABFE1C6F FOREIGN KEY (user_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE travel_preferences ADD CONSTRAINT FK_F6B2907AECAB15B3 FOREIGN KEY (travel_id) REFERENCES travels (uuid)');
        $this->addSql('ALTER TABLE travels ADD CONSTRAINT FK_67FF2BD736D5B633 FOREIGN KEY (driver_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE travels ADD CONSTRAINT FK_67FF2BD71A15440A FOREIGN KEY (car_uuid) REFERENCES cars (uuid)');
        $this->addSql('ALTER TABLE user_travel ADD CONSTRAINT FK_485970F3ABFE1C6F FOREIGN KEY (user_uuid) REFERENCES users (uuid)');
        $this->addSql('ALTER TABLE user_travel ADD CONSTRAINT FK_485970F31A27B30B FOREIGN KEY (travel_uuid) REFERENCES travels (uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cars DROP FOREIGN KEY FK_95C71D1444F5D008');
        $this->addSql('ALTER TABLE cars DROP FOREIGN KEY FK_95C71D147975B7E7');
        $this->addSql('ALTER TABLE car_user DROP FOREIGN KEY FK_46F9C2E51A15440A');
        $this->addSql('ALTER TABLE car_user DROP FOREIGN KEY FK_46F9C2E5ABFE1C6F');
        $this->addSql('ALTER TABLE models DROP FOREIGN KEY FK_E4D6300944F5D008');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F3590D879');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0FABFE1C6F');
        $this->addSql('ALTER TABLE transactions DROP FOREIGN KEY FK_EAA81A4CABFE1C6F');
        $this->addSql('ALTER TABLE travel_preferences DROP FOREIGN KEY FK_F6B2907AECAB15B3');
        $this->addSql('ALTER TABLE travels DROP FOREIGN KEY FK_67FF2BD736D5B633');
        $this->addSql('ALTER TABLE travels DROP FOREIGN KEY FK_67FF2BD71A15440A');
        $this->addSql('ALTER TABLE user_travel DROP FOREIGN KEY FK_485970F3ABFE1C6F');
        $this->addSql('ALTER TABLE user_travel DROP FOREIGN KEY FK_485970F31A27B30B');
        $this->addSql('DROP TABLE brands');
        $this->addSql('DROP TABLE cars');
        $this->addSql('DROP TABLE car_user');
        $this->addSql('DROP TABLE models');
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE transactions');
        $this->addSql('DROP TABLE travel_preferences');
        $this->addSql('DROP TABLE travels');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE user_travel');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
