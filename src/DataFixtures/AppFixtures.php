<?php

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Animal;
use App\Entity\Annonce;
use App\Entity\AnnonceAnimal;
use App\Entity\Demande;
use App\Entity\Session;
use App\Entity\Message;
use App\Entity\Evaluation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        echo "🚀 Début du chargement des fixtures...\n";

        // 1. Créer les clients
        echo "👥 Création des clients...\n";
        $client1 = $this->createClient('Martin', 'Jean', 'client1@petnexus.com', $manager);
        $client2 = $this->createClient('Bernard', 'Marie', 'client2@petnexus.com', $manager);
        $client3 = $this->createClient('Dubois', 'Pierre', 'client3@petnexus.com', $manager);
        $client4 = $this->createClient('Lefevre', 'Sophie', 'client4@petnexus.com', $manager);
        $client5 = $this->createClient('Moreau', 'Thomas', 'client5@petnexus.com', $manager);

        // 2. Créer les animaux
        echo "🐕 Création des animaux...\n";
        $animal1 = $this->createAnimal('Médor', 'Labrador', 3, 'Mâle', $client1, $manager);
        $animal2 = $this->createAnimal('Félix', 'Siamois', 2, 'Mâle', $client1, $manager);
        $animal3 = $this->createAnimal('Rex', 'Berger Allemand', 4, 'Mâle', $client2, $manager);
        $animal4 = $this->createAnimal('Luna', 'Persan', 1, 'Femelle', $client2, $manager);
        $animal5 = $this->createAnimal('Milo', 'Golden Retriever', 2, 'Mâle', $client3, $manager);

        // 3. Créer les annonces
        echo "📢 Création des annonces...\n";
        $annonce1 = $this->createAnnonce('Garde de chat', 'Description garde chat', 'petcare', $client1, $manager);
        $annonce2 = $this->createAnnonce('Adoption chien', 'Description adoption', 'adoption', $client1, $manager);
        $annonce3 = $this->createAnnonce('Échange chien', 'Description échange', 'échange', $client2, $manager);
        $annonce4 = $this->createAnnonce('Pet-sitter chat', 'Description pet-sitter', 'petcare', $client2, $manager);
        $annonce5 = $this->createAnnonce('Garde week-end', 'Description garde week-end', 'petcare', $client3, $manager);

        // 4. Associer animaux aux annonces
        echo "🔗 Association annonces-animaux...\n";
        $this->createAnnonceAnimal($annonce1, $animal2, $manager);
        $this->createAnnonceAnimal($annonce2, $animal1, $manager);
        $this->createAnnonceAnimal($annonce3, $animal3, $manager);
        $this->createAnnonceAnimal($annonce4, $animal4, $manager);
        $this->createAnnonceAnimal($annonce5, $animal5, $manager);

        // 5. Créer les demandes
        echo "📨 Création des demandes...\n";
        $demande1 = $this->createDemande('Message 1', $client2, $client1, $annonce1, 'acceptee', $manager);
        $demande2 = $this->createDemande('Message 2', $client3, $client1, $annonce1, 'en_attente', $manager);
        $demande3 = $this->createDemande('Message 3', $client1, $client2, $annonce3, 'refusee', $manager);
        $demande4 = $this->createDemande('Message 4', $client4, $client3, $annonce5, 'acceptee', $manager);

        // 6. Créer les sessions
        echo "📅 Création des sessions...\n";
        $this->createSession($demande1, 'Session garde chat Félix', 'en_cours', $manager);
        $this->createSession($demande4, 'Session garde week-end Milo', 'terminee', $manager);

        // 7. Créer les messages
        echo "💬 Création des messages...\n";
        $this->createMessage('Bonjour, avez-vous des exigences ?', $client2, $client1, $manager);
        $this->createMessage('Non, juste nourrir et litière.', $client1, $client2, $manager);

        // 8. Créer les évaluations
        echo "⭐ Création des évaluations...\n";
        $this->createEvaluation(5, 'Très bien !', $client1, $client2, $manager);
        $this->createEvaluation(4, 'Communication excellente.', $client2, $client1, $manager);

        $manager->flush();

        echo "✅ Fixtures créées avec succès !\n";
    }

    private function createClient(string $nom, string $prenom, string $email, ObjectManager $manager): Client
    {
        $client = new Client();
        $client->setNom($nom);
        $client->setPrenom($prenom);
        $client->setEmail($email);
        $client->setTelephone('06' . rand(10000000, 99999999));
        $client->setAdresse('Adresse ' . $nom);

        $hashedPassword = $this->passwordHasher->hashPassword($client, 'client123');
        $client->setPassword($hashedPassword);

        $manager->persist($client);
        return $client;
    }

    private function createAnimal(string $nom, string $race, int $age, string $sexe, Client $proprietaire, ObjectManager $manager): Animal
    {
        $animal = new Animal();
        $animal->setNom($nom);
        $animal->setRace($race);
        $animal->setAger($age);
        $animal->setSexe($sexe);
        $animal->setDescription('Description de ' . $nom);
        $animal->setProprietaire($proprietaire);

        $manager->persist($animal);
        return $animal;
    }

    private function createAnnonce(string $titre, string $description, string $type, Client $auteur, ObjectManager $manager): Annonce
    {
        $annonce = new Annonce();
        $annonce->setTitre($titre);
        $annonce->setDescription($description);
        $annonce->setType($type);
        $annonce->setDatePublication(new \DateTime('-5 days'));
        $annonce->setStatut('active');
        $annonce->setAuteur($auteur);

        $manager->persist($annonce);
        return $annonce;
    }

    private function createAnnonceAnimal(Annonce $annonce, Animal $animal, ObjectManager $manager): void
    {
        $annonceAnimal = new AnnonceAnimal();
        $annonceAnimal->setAnnonce($annonce);
        $annonceAnimal->setAnimal($animal);
        $annonceAnimal->setDateDebut(new \DateTime('+7 days'));
        $annonceAnimal->setDateFin(new \DateTime('+21 days'));

        $manager->persist($annonceAnimal);
    }

    private function createDemande(string $message, Client $demandeur, Client $destinataire, Annonce $annonce, string $statut, ObjectManager $manager): Demande
    {
        $demande = new Demande();
        $demande->setMessage($message);

        // Utilisez DateTimeImmutable au lieu de DateTime
        $demande->setDateEnvoi(new \DateTimeImmutable('-3 days'));

        $demande->setStatut($statut);
        $demande->setDemandeur($demandeur);
        $demande->setDestinataire($destinataire);
        $demande->setAnnonce($annonce);

        $manager->persist($demande);
        return $demande;
    }

    private function createSession(Demande $demande, string $description, string $statut, ObjectManager $manager): void
    {
        $session = new Session();
        $session->setDemande($demande);
        $session->setDescription($description);
        $session->setStatut($statut);

        // Utilisez DateTimeImmutable
        $session->setDateDebut(new \DateTimeImmutable('-5 days'));

        if ($statut === 'terminee') {
            $session->setDateFin(new \DateTimeImmutable('-2 days'));
        }

        $manager->persist($session);
    }

    private function createMessage(string $contenu, Client $expediteur, Client $destinataire, ObjectManager $manager): void
    {
        $message = new Message();
        $message->setContenu($contenu);

        // Utilisez DateTimeImmutable
        $message->setDateEnvoi(new \DateTimeImmutable('-1 day'));

        $message->setExpediteur($expediteur);
        $message->setDestinataire($destinataire);

        $manager->persist($message);
    }

    private function createEvaluation(int $note, string $commentaire, Client $evaluateur, Client $evalue, ObjectManager $manager): void
    {
        $evaluation = new Evaluation();
        $evaluation->setNote($note);
        $evaluation->setCommentaire($commentaire);

        // Utilisez DateTime (pas DateTimeImmutable car l'entité Evaluation utilise DateTimeInterface)
        $evaluation->setDateEvaluation(new \DateTime('-10 days'));

        $evaluation->setEvaluateur($evaluateur);
        $evaluation->setEvalue($evalue);

        $manager->persist($evaluation);
    }
}
