<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use App\Repository\EvaluationRepository;
use App\Repository\DemandeRepository;
use App\Repository\MessageRepository;
use App\Repository\AnimalRepository;
use App\Repository\AnnonceRepository;
use App\Repository\SessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('client_dashboard');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(
        UtilisateurRepository $utilisateurRepo,
        EvaluationRepository $evaluationRepo,
        DemandeRepository $demandeRepo,
        MessageRepository $messageRepo,
        AnimalRepository $animalRepo,
        AnnonceRepository $annonceRepo,
        SessionRepository $sessionRepo
    ): Response
    {
        $stats = [
            // Stats utilisateurs
            'total_users' => $utilisateurRepo->countTotalUsers(),
            'total_admins' => $utilisateurRepo->countByType('App\Entity\Admin'),
            'total_clients' => $utilisateurRepo->countByType('App\Entity\Client'),

            // Stats évaluations
            'total_evaluations' => $evaluationRepo->count([]),

            // Stats demandes
            'demandes_en_attente' => $demandeRepo->count(['statut' => 'en_attente']),
            'demandes_acceptees' => $demandeRepo->count(['statut' => 'accepte']),
            'demandes_refusees' => $demandeRepo->count(['statut' => 'refuse']),
            'demandes_total' => $demandeRepo->count([]),

            // Stats messages
            'messages_total' => $messageRepo->count([]),
            'messages_aujourdhui' => $messageRepo->countMessagesToday(),

            // Stats animaux
            'total_animaux' => $animalRepo->count([]),
            'animaux_male' => $animalRepo->count(['sexe' => 'male']),
            'animaux_femelle' => $animalRepo->count(['sexe' => 'femelle']),

            // Stats annonces
            'total_annonces' => $annonceRepo->count([]),
            'annonces_actives' => $annonceRepo->count(['statut' => 'active']),
            'annonces_cloturees' => $annonceRepo->count(['statut' => 'cloturee']),

            // Stats sessions
            'sessions_en_cours' => $sessionRepo->count(['statut' => 'en_cours']),
            'sessions_terminees' => $sessionRepo->count(['statut' => 'terminee']),
            'sessions_total' => $sessionRepo->count([]),
        ];

        return $this->render('dashboard/admin.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/client/dashboard', name: 'client_dashboard')]
    #[IsGranted('ROLE_CLIENT')]
    public function clientDashboard(
        DemandeRepository $demandeRepo,
        MessageRepository $messageRepo,
        AnimalRepository $animalRepo,
        AnnonceRepository $annonceRepo,
        SessionRepository $sessionRepo
    ): Response
    {
        $user = $this->getUser();

        // Statistiques pour le client
        $stats = [
            // Mes animaux
            'mes_animaux' => $animalRepo->count(['proprietaire' => $user]),

            // Mes annonces
            'mes_annonces' => $annonceRepo->count(['auteur' => $user]),
            'annonces_actives' => $annonceRepo->count(['auteur' => $user, 'statut' => 'active']),

            // Mes demandes envoyées
            'demandes_envoyees' => $demandeRepo->count(['demandeur' => $user]),
            'demandes_envoyees_en_attente' => $demandeRepo->count(['demandeur' => $user, 'statut' => 'en_attente']),
            'demandes_envoyees_acceptees' => $demandeRepo->count(['demandeur' => $user, 'statut' => 'accepte']),

            // Demandes reçues
            'demandes_recues' => $demandeRepo->count(['destinataire' => $user]),
            'demandes_recues_en_attente' => $demandeRepo->count(['destinataire' => $user, 'statut' => 'en_attente']),
            'demandes_recues_count' => $demandeRepo->count(['destinataire' => $user, 'statut' => 'en_attente']),

            // Messages
            'messages_recus' => $messageRepo->count(['destinataire' => $user]),
            'nouveaux_messages' => $messageRepo->count(['destinataire' => $user, 'lu' => false]),
            'nouveaux_messages_count' => $messageRepo->count(['destinataire' => $user, 'lu' => false]),

            // Sessions
            'sessions_en_cours' => $sessionRepo->countSessionsByUser($user),
        ];

        return $this->render('dashboard/client.html.twig', $stats);
    }

    // ============ ROUTES CLIENT ============

    #[Route('/client/demandes', name: 'client_demandes')]
    #[IsGranted('ROLE_CLIENT')]
    public function clientDemandes(DemandeRepository $demandeRepo): Response
    {
        $user = $this->getUser();

        $demandesRecues = $demandeRepo->findBy(
            ['destinataire' => $user],
            ['dateEnvoi' => 'DESC']
        );

        $demandesEnvoyees = $demandeRepo->findBy(
            ['demandeur' => $user],
            ['dateEnvoi' => 'DESC']
        );

        return $this->render('client/demandes.html.twig', [
            'demandesRecues' => $demandesRecues,
            'demandesEnvoyees' => $demandesEnvoyees,
        ]);
    }

    #[Route('/client/messages', name: 'client_messages')]
    #[IsGranted('ROLE_CLIENT')]
    public function clientMessages(MessageRepository $messageRepo): Response
    {
        $user = $this->getUser();

        $messagesRecus = $messageRepo->findBy(
            ['destinataire' => $user],
            ['dateEnvoi' => 'DESC']
        );

        $messagesEnvoyes = $messageRepo->findBy(
            ['expediteur' => $user],
            ['dateEnvoi' => 'DESC']
        );

        return $this->render('client/messages.html.twig', [
            'messagesRecus' => $messagesRecus,
            'messagesEnvoyes' => $messagesEnvoyes,
            'nouveaux_messages_count' => $messageRepo->count(['destinataire' => $user, 'lu' => false]),
        ]);
    }

    #[Route('/client/animaux', name: 'client_animaux')]
    #[IsGranted('ROLE_CLIENT')]
    public function clientAnimaux(AnimalRepository $animalRepo): Response
    {
        $user = $this->getUser();
        $animaux = $animalRepo->findBy(['proprietaire' => $user]);

        return $this->render('client/animaux.html.twig', [
            'animaux' => $animaux,
        ]);
    }

    #[Route('/client/annonces', name: 'client_annonces')]
    #[IsGranted('ROLE_CLIENT')]
    public function clientAnnonces(AnnonceRepository $annonceRepo): Response
    {
        $user = $this->getUser();

        $annonces = $annonceRepo->findBy(
            ['auteur' => $user],
            ['datePublication' => 'DESC']
        );

        return $this->render('client/annonces.html.twig', [
            'annonces' => $annonces,
        ]);
    }

    // ============ ROUTES ADMIN ============

    #[Route('/admin/demandes', name: 'admin_demandes')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDemandes(DemandeRepository $demandeRepo): Response
    {
        $demandes = $demandeRepo->findBy(
            [],
            ['dateEnvoi' => 'DESC']
        );

        return $this->render('admin/demandes.html.twig', [
            'demandes' => $demandes,
        ]);
    }

    #[Route('/admin/messages', name: 'admin_messages')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminMessages(MessageRepository $messageRepo): Response
    {
        $messages = $messageRepo->findBy(
            [],
            ['dateEnvoi' => 'DESC'],
            100
        );

        return $this->render('admin/messages.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/admin/animaux', name: 'admin_animaux')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminAnimaux(AnimalRepository $animalRepo): Response
    {
        $animaux = $animalRepo->findBy(
            [],
            ['id' => 'DESC']
        );

        return $this->render('admin/animaux.html.twig', [
            'animaux' => $animaux,
        ]);
    }

    #[Route('/admin/annonces', name: 'admin_annonces')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminAnnonces(AnnonceRepository $annonceRepo): Response
    {
        $annonces = $annonceRepo->findBy(
            [],
            ['datePublication' => 'DESC']
        );

        return $this->render('admin/annonces.html.twig', [
            'annonces' => $annonces,
        ]);
    }

    #[Route('/admin/sessions', name: 'admin_sessions')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminSessions(SessionRepository $sessionRepo): Response
    {
        $sessions = $sessionRepo->findBy(
            [],
            ['dateDebut' => 'DESC']
        );

        return $this->render('admin/sessions.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/admin/parametres', name: 'admin_parametres')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminParametres(): Response
    {
        return $this->render('admin/parametres.html.twig');
    }
}
