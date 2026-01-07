<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241203000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create all tables for Pet Platform with AnnonceAnimal entity';
    }

    public function up(Schema $schema): void
    {
        // Table utilisateur (avec héritage SINGLE_TABLE)
        $this->addSql('CREATE TABLE utilisateur (
            id INT AUTO_INCREMENT NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            email VARCHAR(180) NOT NULL,
            password VARCHAR(255) NOT NULL,
            photo_profil VARCHAR(255) DEFAULT NULL,
            adresse LONGTEXT DEFAULT NULL,
            telephone VARCHAR(20) DEFAULT NULL,
            roles JSON NOT NULL,
            type VARCHAR(255) NOT NULL,
            UNIQUE INDEX UNIQ_1D1C63B3E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table animal
        $this->addSql('CREATE TABLE animal (
            id INT AUTO_INCREMENT NOT NULL,
            proprietaire_id INT NOT NULL,
            nom VARCHAR(255) NOT NULL,
            race VARCHAR(255) NOT NULL,
            ager INT NOT NULL,
            sexe VARCHAR(10) NOT NULL,
            photo VARCHAR(255) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            INDEX IDX_6AAB231F76C50E4A (proprietaire_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table annonce
        $this->addSql('CREATE TABLE annonce (
            id INT AUTO_INCREMENT NOT NULL,
            auteur_id INT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            description LONGTEXT NOT NULL,
            type VARCHAR(255) NOT NULL,
            date_publication DATETIME NOT NULL,
            statut VARCHAR(50) NOT NULL,
            INDEX IDX_F65593E560BB6FE6 (auteur_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table annonce_animal (entité avec dates)
        $this->addSql('CREATE TABLE annonce_animal (
            id INT AUTO_INCREMENT NOT NULL,
            annonce_id INT NOT NULL,
            animal_id INT NOT NULL,
            date_debut DATE NOT NULL,
            date_fin DATE NOT NULL,
            INDEX IDX_BD5F8A318805AB2F (annonce_id),
            INDEX IDX_BD5F8A318E962C16 (animal_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table demande
        $this->addSql('CREATE TABLE demande (
            id INT AUTO_INCREMENT NOT NULL,
            demandeur_id INT NOT NULL,
            destinataire_id INT NOT NULL,
            annonce_id INT NOT NULL,
            date_envoi DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            statut VARCHAR(50) NOT NULL,
            message LONGTEXT DEFAULT NULL,
            INDEX IDX_85E36167F97C08F1 (demandeur_id),
            INDEX IDX_85E36167A4F84F6E (destinataire_id),
            INDEX IDX_85E361678805AB2F (annonce_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table session
        $this->addSql('CREATE TABLE session (
            id INT AUTO_INCREMENT NOT NULL,
            demande_id INT NOT NULL,
            date_debut DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            date_fin DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            statut VARCHAR(50) NOT NULL,
            description LONGTEXT DEFAULT NULL,
            UNIQUE INDEX UNIQ_D044D5D4F97C08F1 (demande_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table message
        $this->addSql('CREATE TABLE message (
            id INT AUTO_INCREMENT NOT NULL,
            expediteur_id INT NOT NULL,
            destinataire_id INT NOT NULL,
            contenu LONGTEXT NOT NULL,
            date_envoi DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_B6BD307F10335F61 (expediteur_id),
            INDEX IDX_B6BD307FA4F84F6E (destinataire_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Table evaluation
        $this->addSql('CREATE TABLE evaluation (
            id INT AUTO_INCREMENT NOT NULL,
            evaluateur_id INT NOT NULL,
            evalue_id INT NOT NULL,
            note INT NOT NULL,
            commentaire LONGTEXT DEFAULT NULL,
            date_evaluation DATETIME NOT NULL,
            INDEX IDX_1323A575F97C08F1 (evaluateur_id),
            INDEX IDX_1323A575F97C08F2 (evalue_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Contraintes de clés étrangères
        $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E560BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE annonce_animal ADD CONSTRAINT FK_BD5F8A318805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE annonce_animal ADD CONSTRAINT FK_BD5F8A318E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_85E36167F97C08F1 FOREIGN KEY (demandeur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_85E36167A4F84F6E FOREIGN KEY (destinataire_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_85E361678805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4F97C08F1 FOREIGN KEY (demande_id) REFERENCES demande (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575F97C08F1 FOREIGN KEY (evaluateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575F97C08F2 FOREIGN KEY (evalue_id) REFERENCES utilisateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE animal DROP FOREIGN KEY FK_6AAB231F76C50E4A');
        $this->addSql('ALTER TABLE annonce DROP FOREIGN KEY FK_F65593E560BB6FE6');
        $this->addSql('ALTER TABLE annonce_animal DROP FOREIGN KEY FK_BD5F8A318805AB2F');
        $this->addSql('ALTER TABLE annonce_animal DROP FOREIGN KEY FK_BD5F8A318E962C16');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_85E36167F97C08F1');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_85E36167A4F84F6E');
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_85E361678805AB2F');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4F97C08F1');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FA4F84F6E');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575F97C08F1');
        $this->addSql('ALTER TABLE evaluation DROP FOREIGN KEY FK_1323A575F97C08F2');

        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE animal');
        $this->addSql('DROP TABLE annonce');
        $this->addSql('DROP TABLE annonce_animal');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE evaluation');
    }
}
