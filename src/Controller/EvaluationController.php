<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Evaluation;
use App\Form\EvaluationType;
use App\Repository\ClientRepository;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/client/evaluation')]
#[IsGranted('ROLE_CLIENT')]
class EvaluationController extends AbstractController
{
    #[Route('/', name: 'evaluation_index', methods: ['GET'])]
    public function index(
        EvaluationRepository $evaluationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Évaluations reçues par l'utilisateur connecté
        $evaluations = [];
        $moyenne = 0;
        $usersToEvaluate = [];

        if ($user instanceof \App\Entity\Client || $this->isGranted('ROLE_CLIENT')) {
            $user = $this->getUser();
            $evaluations = $evaluationRepository->findByEvalue($user);
            $moyenne = $evaluationRepository->getAverageNoteForUser($user);

            // Récupérer TOUS les utilisateurs (sauf soi-même et les admins)
            $allUsers = $entityManager->getRepository(\App\Entity\Utilisateur::class)->findAll();
            $potentialUsers = [];

            foreach ($allUsers as $u) {
                // Exclure soi-même et les admins
                if ($u->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $u->getRoles())) {
                    $potentialUsers[] = $u;
                }
            }

            // Filtrer ceux déjà évalués
            foreach ($potentialUsers as $potentialUser) {
                $existingEvaluation = $evaluationRepository->findOneBy([
                    'evaluateur' => $user,
                    'evalue' => $potentialUser
                ]);

                if (!$existingEvaluation) {
                    $usersToEvaluate[] = $potentialUser;
                }
            }
        }

        return $this->render('evaluation/index.html.twig', [
            'evaluations' => $evaluations,
            'moyenne' => $moyenne,
            'total' => count($evaluations),
            'usersToEvaluate' => $usersToEvaluate,
        ]);
    }

    #[Route('/new/{id}', name: 'evaluation_new', methods: ['GET', 'POST'])]
    public function new(
        Client $client,
        Request $request,
        EntityManagerInterface $entityManager,
        \App\Repository\DemandeRepository $demandeRepository // Injection du repository
    ): Response {
        $currentUser = $this->getUser();

        // Empêcher un utilisateur de s'auto-évaluer
        if ($currentUser->getId() === $client->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous évaluer vous-même !');
            return $this->redirectToRoute('client_dashboard');
        }



        $evaluation = new Evaluation();
        $evaluation->setEvaluateur($currentUser);
        $evaluation->setEvalue($client);
        $evaluation->setDateEvaluation(new \DateTime());

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evaluation);
            $entityManager->flush();

            $this->addFlash('success', 'Votre évaluation a été enregistrée avec succès !');

            return $this->redirectToRoute('evaluation_index');
        }

        return $this->render('evaluation/new.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form->createView(),
            'client' => $client,
        ]);
    }

    #[Route('/{id}', name: 'evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    #[Route('/{id}/edit', name: 'evaluation_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Evaluation $evaluation,
        EntityManagerInterface $entityManager
    ): Response {
        // Seul l'évaluateur peut modifier son évaluation
        if ($evaluation->getEvaluateur()->getId() !== $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cette évaluation !');
            return $this->redirectToRoute('evaluation_index');
        }

        $form = $this->createForm(EvaluationType::class, $evaluation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Évaluation modifiée avec succès !');

            return $this->redirectToRoute('evaluation_index');
        }

        return $this->render('evaluation/edit.html.twig', [
            'evaluation' => $evaluation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'evaluation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Evaluation $evaluation,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        // Seul l'évaluateur ou un admin peut supprimer
        if ($evaluation->getEvaluateur()->getId() !== $user->getId() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cette évaluation !');
            return $this->redirectToRoute('evaluation_index');
        }

        if ($this->isCsrfTokenValid('delete' . $evaluation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();

            $this->addFlash('success', 'Évaluation supprimée avec succès !');
        }

        return $this->redirectToRoute('evaluation_index');
    }

    #[Route('/client/{id}', name: 'evaluation_client_list', methods: ['GET'])]
    public function clientEvaluations(
        Client $client,
        EvaluationRepository $evaluationRepository
    ): Response {
        $evaluations = $evaluationRepository->findByEvalue($client);
        $moyenne = $evaluationRepository->getAverageNoteForUser($client);

        return $this->render('evaluation/client_evaluations.html.twig', [
            'client' => $client,
            'evaluations' => $evaluations,
            'moyenne' => $moyenne,
        ]);
    }
}
