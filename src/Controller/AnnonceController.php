<?php

namespace App\Controller;

use App\Entity\Annonce;
use App\Form\AnnonceType;
use App\Form\DemandeType;
use App\Repository\AnnonceRepository;
use App\Repository\DemandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/annonce')]
final class AnnonceController extends AbstractController
{
    #[Route(name: 'app_annonce_index', methods: ['GET'])]
    public function index(AnnonceRepository $annonceRepository): Response
    {
        // Redirection pour les clients vers leur dashboard spécifique
        if ($this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('client_annonces');
        }

        // Pour les admins, toutes les annonces
        $annonces = $annonceRepository->findBy([], ['datePublication' => 'DESC']);

        return $this->render('annonce/index.html.twig', [
            'annonces' => $annonces,
        ]);
    }

    #[Route('/new', name: 'app_annonce_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CLIENT')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $annonce = new Annonce();
        $annonce->setAuteur($this->getUser());
        $annonce->setDatePublication(new \DateTime());

        $form = $this->createForm(AnnonceType::class, $annonce, [
            'user' => $this->getUser()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'Annonce créée avec succès !');
            return $this->redirectToRoute('app_annonce_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('annonce/new.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_annonce_show', methods: ['GET'])]
    public function show(Annonce $annonce): Response
    {
        // Vérifier les autorisations
        if ($this->isGranted('ROLE_CLIENT') && !$this->isGranted('ROLE_ADMIN') && $annonce->getAuteur() !== $this->getUser()) {
            // Les clients ne peuvent voir que leurs propres annonces
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette annonce.');
        }

        return $this->render('annonce/show.html.twig', [
            'annonce' => $annonce,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_annonce_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur
        if ($annonce->getAuteur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cette annonce.');
        }

        $form = $this->createForm(AnnonceType::class, $annonce, [
            'user' => $this->getUser()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Annonce modifiée avec succès !');
            return $this->redirectToRoute('app_annonce_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('annonce/edit.html.twig', [
            'annonce' => $annonce,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_annonce_delete', methods: ['POST'])]
    public function delete(Request $request, Annonce $annonce, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est l'auteur
        if ($annonce->getAuteur() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cette annonce.');
        }

        if ($this->isCsrfTokenValid('delete' . $annonce->getId(), $request->request->get('_token'))) {
            $entityManager->remove($annonce);
            $entityManager->flush();

            $this->addFlash('success', 'Annonce supprimée avec succès !');
        }

        return $this->redirectToRoute('app_annonce_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/public/list', name: 'app_annonce_public', methods: ['GET'])]
    public function publicList(AnnonceRepository $annonceRepository): Response
    {
        // Liste publique des annonces actives
        $annonces = $annonceRepository->findBy(
            ['statut' => 'active'],
            ['datePublication' => 'DESC']
        );

        return $this->render('annonce/public_list.html.twig', [
            'annonces' => $annonces,
        ]);
    }
}
