<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203194924 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE animal (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, race VARCHAR(255) NOT NULL, ager INT NOT NULL, sexe VARCHAR(10) NOT NULL, photo VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE annonce (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, type VARCHAR(255) NOT NULL, date_publication DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE annonce_animal (annonce_id INT NOT NULL, animal_id INT NOT NULL, INDEX IDX_A0C20018805AB2F (annonce_id), INDEX IDX_A0C20018E962C16 (animal_id), PRIMARY KEY (annonce_id, animal_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE demande (id INT AUTO_INCREMENT NOT NULL, date_envoi DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME DEFAULT NULL, statut VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, demande_id INT NOT NULL, UNIQUE INDEX UNIQ_D044D5D480E95E18 (demande_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE annonce_animal ADD CONSTRAINT FK_A0C20018805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE annonce_animal ADD CONSTRAINT FK_A0C20018E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D480E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE annonce_animal DROP FOREIGN KEY FK_A0C20018805AB2F');
        $this->addSql('ALTER TABLE annonce_animal DROP FOREIGN KEY FK_A0C20018E962C16');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D480E95E18');
        $this->addSql('DROP TABLE animal');
        $this->addSql('DROP TABLE annonce');
        $this->addSql('DROP TABLE annonce_animal');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
