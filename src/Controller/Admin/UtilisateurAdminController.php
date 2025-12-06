<?php

namespace App\Controller\Admin;

use App\Entity\Admin;
use App\Entity\Client;
use App\Entity\Utilisateur;
use App\Form\AdminUserType;
use App\Form\ClientUserType;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurAdminController extends AbstractController
{
    #[Route('/', name: 'admin_utilisateur_index', methods: ['GET'])]
    public function index(UtilisateurRepository $utilisateurRepository): Response
    {
        $utilisateurs = $utilisateurRepository->findAll();

        return $this->render('admin/utilisateur/index.html.twig', [
            'utilisateurs' => $utilisateurs,
        ]);
    }

    #[Route('/new-client', name: 'admin_utilisateur_new_client', methods: ['GET', 'POST'])]
    public function newClient(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $client = new Client();
        $form = $this->createForm(ClientUserType::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $client->setPassword($passwordHasher->hashPassword($client, $plainPassword));

            $entityManager->persist($client);
            $entityManager->flush();

            $this->addFlash('success', 'Client créé avec succès !');

            return $this->redirectToRoute('admin_utilisateur_index');
        }

        return $this->render('admin/utilisateur/new_client.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/new-admin', name: 'admin_utilisateur_new_admin', methods: ['GET', 'POST'])]
    public function newAdmin(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $admin = new Admin();
        $form = $this->createForm(AdminUserType::class, $admin);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            $admin->setPassword($passwordHasher->hashPassword($admin, $plainPassword));

            $entityManager->persist($admin);
            $entityManager->flush();

            $this->addFlash('success', 'Administrateur créé avec succès !');

            return $this->redirectToRoute('admin_utilisateur_index');
        }

        return $this->render('admin/utilisateur/new_admin.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_utilisateur_show', methods: ['GET'])]
    public function show(Utilisateur $utilisateur): Response
    {
        return $this->render('admin/utilisateur/show.html.twig', [
            'utilisateur' => $utilisateur,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_utilisateur_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Utilisateur $utilisateur,
        EntityManagerInterface $entityManager
    ): Response {
        // Empêcher un admin de se supprimer lui-même
        if ($utilisateur->getId() === $this->getUser()->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte !');
            return $this->redirectToRoute('admin_utilisateur_index');
        }

        if ($this->isCsrfTokenValid('delete'.$utilisateur->getId(), $request->request->get('_token'))) {
            // Supprimer la photo de profil si elle existe
            if ($utilisateur->getPhotoProfil()) {
                $photoPath = $this->getParameter('photos_directory').'/'.$utilisateur->getPhotoProfil();
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }

            $entityManager->remove($utilisateur);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur supprimé avec succès !');
        }

        return $this->redirectToRoute('admin_utilisateur_index');
    }
}
