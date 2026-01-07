<?php

namespace App\Controller;

use App\Entity\Session;
use App\Form\SessionType;
use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/session')]
final class SessionController extends AbstractController
{
    #[Route('/', name: 'app_session_index', methods: ['GET'])]
    public function index(SessionRepository $sessionRepository): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // Filtrer les sessions selon l'utilisateur
        if ($this->isGranted('ROLE_ADMIN')) {
            $sessions = $sessionRepository->findAll();
        } else {
            $sessions = $sessionRepository->findByUser($this->getUser());
        }

        return $this->render('session/index.html.twig', [
            'sessions' => $sessions,
        ]);
    }

    #[Route('/new', name: 'app_session_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $session = new Session();
        $form = $this->createForm(SessionType::class, $session, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'La session a été créée avec succès.');
            return $this->redirectToRoute('app_session_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('session/new.html.twig', [
            'session' => $session,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_session_show', methods: ['GET'])]
    public function show(Session $session): Response
    {
        // Vérifier les permissions
        if (!$this->checkSessionPermission($session)) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_session_index');
        }

        return $this->render('session/show.html.twig', [
            'session' => $session,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_session_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les permissions
        if (!$this->checkSessionPermission($session)) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette session.');
            return $this->redirectToRoute('app_session_index');
        }

        $form = $this->createForm(SessionType::class, $session, [
            'user' => $this->getUser(),
            'edit_mode' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La session a été modifiée avec succès.');
            return $this->redirectToRoute('app_session_show', ['id' => $session->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('session/edit.html.twig', [
            'session' => $session,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_session_delete', methods: ['POST'])]
    public function delete(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les permissions
        if (!$this->checkSessionPermission($session)) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette session.');
            return $this->redirectToRoute('app_session_index');
        }

        if ($this->isCsrfTokenValid('delete'.$session->getId(), $request->request->get('_token'))) {
            $entityManager->remove($session);
            $entityManager->flush();

            $this->addFlash('success', 'La session a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_session_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/terminate', name: 'app_session_terminate', methods: ['POST'])]
    public function terminate(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut terminer la session
        if (!$this->checkSessionPermission($session)) {
            $this->addFlash('error', 'Vous ne pouvez pas terminer cette session.');
            return $this->redirectToRoute('app_session_index');
        }

        if ($this->isCsrfTokenValid('terminate'.$session->getId(), $request->request->get('_token'))) {
            $session->setStatut('terminee');
            $session->setDateFin(new \DateTimeImmutable());
            $entityManager->flush();

            $this->addFlash('success', 'La session a été terminée avec succès.');
        }

        return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
    }

    #[Route('/{id}/cancel', name: 'app_session_cancel', methods: ['POST'])]
    public function cancel(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        // Vérifier que l'utilisateur peut annuler la session
        if (!$this->checkSessionPermission($session)) {
            $this->addFlash('error', 'Vous ne pouvez pas annuler cette session.');
            return $this->redirectToRoute('app_session_index');
        }

        if ($this->isCsrfTokenValid('cancel'.$session->getId(), $request->request->get('_token'))) {
            $session->setStatut('annulee');
            $entityManager->flush();

            $this->addFlash('warning', 'La session a été annulée.');
        }

        return $this->redirectToRoute('app_session_show', ['id' => $session->getId()]);
    }

    /**
     * Vérifie les permissions pour une session
     */
    private function checkSessionPermission(Session $session): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        // Vérifier si l'utilisateur est concerné par la session
        $demande = $session->getDemande();
        if (!$demande) {
            return false;
        }

        // L'utilisateur peut voir la session s'il est demandeur ou destinataire de la demande
        return $demande->getDemandeur()->getId() === $user->getId()
            || $demande->getDestinataire()->getId() === $user->getId();
    }
}
