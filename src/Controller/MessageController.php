<?php

namespace App\Controller;

use App\Entity\Message;
use App\Form\MessageType;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/message')]
final class MessageController extends AbstractController
{
    #[Route('/', name: 'app_message_index', methods: ['GET'])]
    public function index(MessageRepository $messageRepository): Response
    {
        // Vérifier si l'utilisateur est connecté
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // Filtrer les messages selon l'utilisateur
        if ($this->isGranted('ROLE_ADMIN')) {
            $messages = $messageRepository->findAll();
        } else {
            $messages = $messageRepository->findByUser($this->getUser());
        }

        return $this->render('message/index.html.twig', [
            'messages' => $messages,
        ]);
    }

    #[Route('/new', name: 'app_message_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        $message = new Message();

        // Pré-remplir l'expéditeur
        $message->setExpediteur($this->getUser());

        // Pré-remplir le destinataire si fourni dans l'URL
        $destinataireId = $request->query->get('destinataire');
        if ($destinataireId) {
            $destinataire = $entityManager->getRepository(\App\Entity\Utilisateur::class)->find($destinataireId);
            if ($destinataire) {
                $message->setDestinataire($destinataire);
            }
        }

        $form = $this->createForm(MessageType::class, $message, [
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            $this->addFlash('success', 'Le message a été envoyé avec succès.');
            return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('message/new.html.twig', [
            'message' => $message,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_message_show', methods: ['GET'])]
    public function show(Message $message): Response
    {
        // Vérifier les permissions
        if (!$this->checkMessagePermission($message)) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('app_message_index');
        }

        return $this->render('message/show.html.twig', [
            'message' => $message,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_message_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les permissions (seul l'expéditeur peut modifier)
        if (!$this->getUser() || $message->getExpediteur()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier ce message.');
            return $this->redirectToRoute('app_message_index');
        }

        $form = $this->createForm(MessageType::class, $message, [
            'user' => $this->getUser(),
            'edit_mode' => true,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Le message a été modifié avec succès.');
            return $this->redirectToRoute('app_message_show', ['id' => $message->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('message/edit.html.twig', [
            'message' => $message,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_message_delete', methods: ['POST'])]
    public function delete(Request $request, Message $message, EntityManagerInterface $entityManager): Response
    {
        // Vérifier les permissions (expéditeur ou admin)
        if (!$this->checkMessagePermission($message, true)) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer ce message.');
            return $this->redirectToRoute('app_message_index');
        }

        if ($this->isCsrfTokenValid('delete'.$message->getId(), $request->request->get('_token'))) {
            $entityManager->remove($message);
            $entityManager->flush();

            $this->addFlash('success', 'Le message a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_message_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/conversation/{userId}', name: 'app_message_conversation', methods: ['GET'])]
    public function conversation(int $userId, MessageRepository $messageRepository): Response
    {
        if (!$this->getUser()) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer la conversation entre les deux utilisateurs
        $conversation = $messageRepository->findConversation($this->getUser()->getId(), $userId);
        $otherUser = $entityManager->getRepository(\App\Entity\Utilisateur::class)->find($userId);

        if (!$otherUser) {
            $this->addFlash('error', 'Utilisateur non trouvé.');
            return $this->redirectToRoute('app_message_index');
        }

        return $this->render('message/conversation.html.twig', [
            'conversation' => $conversation,
            'otherUser' => $otherUser,
        ]);
    }

    /**
     * Vérifie les permissions pour un message
     */
    private function checkMessagePermission(Message $message, bool $forDeletion = false): bool
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        // Pour la suppression, seul l'expéditeur peut supprimer
        if ($forDeletion) {
            return $message->getExpediteur()->getId() === $user->getId();
        }

        // Pour la lecture, expéditeur ou destinataire
        return $message->getExpediteur()->getId() === $user->getId()
            || $message->getDestinataire()->getId() === $user->getId();
    }
}
