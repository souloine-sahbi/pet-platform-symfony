<?php

namespace App\Controller\Admin;

use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/evaluations')]
#[IsGranted('ROLE_ADMIN')]
class EvaluationAdminController extends AbstractController
{
    #[Route('/', name: 'admin_evaluation_index', methods: ['GET'])]
    public function index(EvaluationRepository $evaluationRepository): Response
    {
        $evaluations = $evaluationRepository->findBy([], ['dateEvaluation' => 'DESC']);

        $stats = [
            'total' => $evaluationRepository->countTotal(),
            'moyenne' => $evaluationRepository->getAverageNote(),
        ];

        return $this->render('admin/evaluation/index.html.twig', [
            'evaluations' => $evaluations,
            'stats' => $stats,
        ]);
    }

    #[Route('/{id}', name: 'admin_evaluation_show', methods: ['GET'])]
    public function show(Evaluation $evaluation): Response
    {
        return $this->render('admin/evaluation/show.html.twig', [
            'evaluation' => $evaluation,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_evaluation_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Evaluation $evaluation,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$evaluation->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evaluation);
            $entityManager->flush();

            $this->addFlash('success', 'Évaluation supprimée avec succès !');
        }

        return $this->redirectToRoute('admin_evaluation_index');
    }
}
