<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260106230629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing tables for Pet Platform';
    }

    public function up(Schema $schema): void
    {
        // Vérifions d'abord quelles tables existent déjà
        $this->skipIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on MySQL.');

        // Table annonce_animal (si elle n'existe pas)
        if (!$schema->hasTable('annonce_animal')) {
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

            $this->addSql('ALTER TABLE annonce_animal ADD CONSTRAINT FK_BD5F8A318805AB2F FOREIGN KEY (annonce_id) REFERENCES annonce (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE annonce_animal ADD CONSTRAINT FK_BD5F8A318E962C16 FOREIGN KEY (animal_id) REFERENCES animal (id) ON DELETE CASCADE');
        }

        // Table session (si elle n'existe pas)
        if (!$schema->hasTable('session')) {
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

            $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4F97C08F1 FOREIGN KEY (demande_id) REFERENCES demande (id)');
        }

        // Table message (si elle n'existe pas)
        if (!$schema->hasTable('message')) {
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

            $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES utilisateur (id)');
            $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FA4F84F6E FOREIGN KEY (destinataire_id) REFERENCES utilisateur (id)');
        }

        // Table evaluation (si elle n'existe pas)
        if (!$schema->hasTable('evaluation')) {
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

            $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575F97C08F1 FOREIGN KEY (evaluateur_id) REFERENCES utilisateur (id)');
            $this->addSql('ALTER TABLE evaluation ADD CONSTRAINT FK_1323A575F97C08F2 FOREIGN KEY (evalue_id) REFERENCES utilisateur (id)');
        }

        // Ajouter les colonnes manquantes aux tables existantes

        // Vérifier si la colonne proprietaire_id existe dans animal
        $table = $schema->getTable('animal');
        if (!$table->hasColumn('proprietaire_id')) {
            $this->addSql('ALTER TABLE animal ADD proprietaire_id INT NOT NULL');
            $this->addSql('CREATE INDEX IDX_6AAB231F76C50E4A ON animal (proprietaire_id)');
            $this->addSql('ALTER TABLE animal ADD CONSTRAINT FK_6AAB231F76C50E4A FOREIGN KEY (proprietaire_id) REFERENCES utilisateur (id)');
        }

        // Vérifier si la colonne auteur_id existe dans annonce
        $table = $schema->getTable('annonce');
        if (!$table->hasColumn('auteur_id')) {
            $this->addSql('ALTER TABLE annonce ADD auteur_id INT NOT NULL');
            $this->addSql('CREATE INDEX IDX_F65593E560BB6FE6 ON annonce (auteur_id)');
            $this->addSql('ALTER TABLE annonce ADD CONSTRAINT FK_F65593E560BB6FE6 FOREIGN KEY (auteur_id) REFERENCES utilisateur (id)');
        }

        // Vérifier si la colonne type existe dans utilisateur (pour l'héritage)
        $table = $schema->getTable('utilisateur');
        if (!$table->hasColumn('type')) {
            $this->addSql('ALTER TABLE utilisateur ADD type VARCHAR(255) NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // Cette migration ne peut pas être rollback facilement
        // car elle ajoute des tables et colonnes
        $this->throwIrreversibleMigrationException();
    }
}
