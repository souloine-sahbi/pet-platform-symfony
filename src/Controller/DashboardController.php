<?php

namespace App\Controller;

use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\EvaluationRepository;


class DashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();

        // Rediriger selon le rôle
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        return $this->redirectToRoute('client_dashboard');
    }

    #[Route('/admin/dashboard', name: 'admin_dashboard')]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDashboard(UtilisateurRepository $utilisateurRepo,EvaluationRepository $evaluationRepo): Response
    {
        $stats = [
            'total_users' => $utilisateurRepo->countTotalUsers(),
            'total_admins' => $utilisateurRepo->countByType('App\Entity\Admin'),
            'total_clients' => $utilisateurRepo->countByType('App\Entity\Client'),
            'total_evaluations' => $evaluationRepo->count([]),  // Add this line

        ];

        return $this->render('dashboard/admin.html.twig', [
            'stats' => $stats,
        ]);
    }

    #[Route('/client/dashboard', name: 'client_dashboard')]
    #[IsGranted('ROLE_CLIENT')]
    public function clientDashboard(): Response
    {
        return $this->render('dashboard/client.html.twig');
    }
}
