<?php

namespace App\Controller;

use App\Entity\Demande;
use App\Entity\Session;
use App\Form\DemandeType;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/demande')]
final class DemandeController extends AbstractController
{
    #[Route('/', name: 'app_demande_index', methods: ['GET'])]
    // Dans DemandeController.php, modifiez la méthode index :
    public function index(DemandeRepository $demandeRepository): Response
    {
        // Vérifiez d'abord si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            return $this->redirectToRoute('app_login');
        }

        // Utilisez isGranted() au lieu de getRole()
        if ($this->isGranted('ROLE_ADMIN')) {
            // Pour les admins, afficher toutes les demandes
            $demandes = $demandeRepository->findAll();
        } else {
            // Pour les clients, afficher seulement leurs demandes
            $demandes = $demandeRepository->findByUser($this->getUser());
        }

        return $this->render('demande/index.html.twig', [
            'demandes' => $demandes,
        ]);
    }

    #[Route('/new', name: 'app_demande_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AnnonceRepository $annonceRepo = null): Response
    {
        $demande = new Demande();

        // Si une annonce_id est passée en paramètre
        $annonceId = $request->query->get('annonce_id');
        if ($annonceId && $annonceRepo) {
            $annonce = $annonceRepo->find($annonceId);

            if ($annonce) {
                // Pré-remplir la demande avec l'annonce
                $demande->setAnnonce($annonce);
                $demande->setDestinataire($annonce->getAuteur());

                // Vérifier que l'utilisateur n'est pas l'auteur
                if ($annonce->getAuteur() === $this->getUser()) {
                    $this->addFlash('warning', 'Vous ne pouvez pas faire une demande sur votre propre annonce.');
                    return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
                }

                // Vérifier si une demande existe déjà
                $existingDemande = $entityManager->getRepository(Demande::class)
                    ->findOneBy([
                        'annonce' => $annonce,
                        'demandeur' => $this->getUser()
                    ]);

                if ($existingDemande) {
                    $this->addFlash('info', 'Vous avez déjà fait une demande pour cette annonce.');
                    return $this->redirectToRoute('app_annonce_show', ['id' => $annonce->getId()]);
                }
            }
        }

        // Définir le demandeur
        $demande->setDemandeur($this->getUser());

        $form = $this->createForm(DemandeType::class, $demande);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($demande);
            $entityManager->flush();

            $this->addFlash('success', 'Votre demande a été envoyée avec succès !');

            // Rediriger vers la page de l'annonce
            if ($demande->getAnnonce()) {
                return $this->redirectToRoute('app_annonce_show', ['id' => $demande->getAnnonce()->getId()]);
            }

            return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande/new.html.twig', [
            'demande' => $demande,
            'form' => $form->createView(),
            'annonce' => $annonce ?? null,
        ]);
    }

    #[Route('/{id}', name: 'app_demande_show', methods: ['GET'])]
    public function show(Demande $demande): Response
    {
        // Vérifier les permissions
        if ($this->getUser()->getRole() !== 'ROLE_ADMIN' &&
            $demande->getDemandeur()->getId() !== $this->getUser()->getId() &&
            $demande->getDestinataire()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous n\'avez pas accès à cette demande.');
            return $this->redirectToRoute('app_demande_index');
        }

        return $this->render('demande/show.html.twig', [
            'demande' => $demande,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_demande_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les permissions (seul le demandeur ou admin peut modifier)
        if ($this->getUser()->getRole() !== 'ROLE_ADMIN' &&
            $demande->getDemandeur()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette demande.');
            return $this->redirectToRoute('app_demande_index');
        }

        $form = $this->createForm(DemandeType::class, $demande, [
            'user' => $this->getUser(),
            'edit_mode' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La demande a été modifiée avec succès.');
            return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('demande/edit.html.twig', [
            'demande' => $demande,
            'form' => $form,
            'editMode' => true,
        ]);
    }

    #[Route('/{id}', name: 'app_demande_delete', methods: ['POST'])]
    public function delete(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les permissions
        if ($this->getUser()->getRole() !== 'ROLE_ADMIN' &&
            $demande->getDemandeur()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette demande.');
            return $this->redirectToRoute('app_demande_index');
        }

        if ($this->isCsrfTokenValid('delete'.$demande->getId(), $request->request->get('_token'))) {
            // Supprimer d'abord la session associée si elle existe
            if ($demande->getSession()) {
                $entityManager->remove($demande->getSession());
            }

            $entityManager->remove($demande);
            $entityManager->flush();

            $this->addFlash('success', 'La demande a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_demande_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/accept', name: 'app_demande_accept', methods: ['POST'])]
    public function accept(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que seul le destinataire peut accepter
        if ($demande->getDestinataire()->getId() !== $this->getUser()->getId() &&
            $this->getUser()->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Seul le destinataire peut accepter cette demande.');
            return $this->redirectToRoute('app_demande_index');
        }

        if ($this->isCsrfTokenValid('accept'.$demande->getId(), $request->request->get('_token'))) {
            $demande->setStatut('acceptee');

            // Créer automatiquement une session
            $session = new Session();
            $session->setDemande($demande);
            $session->setStatut('en_cours');
            $session->setDescription('Session créée automatiquement suite à l\'acceptation de la demande #' . $demande->getId());

            // Remplir les dates avec celles de l'annonce si disponibles
            $annonceAnimal = $demande->getAnnonce()->getAnnonceAnimals()->first();
            if ($annonceAnimal) {
                $session->setDateDebut($annonceAnimal->getDateDebut());
                $session->setDateFin($annonceAnimal->getDateFin());
            }

            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'La demande a été acceptée et une session a été créée.');
        }

        return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
    }

    #[Route('/{id}/refuse', name: 'app_demande_refuse', methods: ['POST'])]
    public function refuse(Request $request, Demande $demande, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que seul le destinataire peut refuser
        if ($demande->getDestinataire()->getId() !== $this->getUser()->getId() &&
            $this->getUser()->getRole() !== 'ROLE_ADMIN') {
            $this->addFlash('error', 'Seul le destinataire peut refuser cette demande.');
            return $this->redirectToRoute('app_demande_index');
        }

        if ($this->isCsrfTokenValid('refuse'.$demande->getId(), $request->request->get('_token'))) {
            $demande->setStatut('refusee');
            $entityManager->flush();

            $this->addFlash('warning', 'La demande a été refusée.');
        }

        return $this->redirectToRoute('app_demande_show', ['id' => $demande->getId()]);
    }
}
