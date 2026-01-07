<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Form\AnimalType;
use App\Repository\AnimalRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/animal')]
final class AnimalController extends AbstractController
{
    private $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    #[Route(name: 'app_animal_index', methods: ['GET'])]
    public function index(AnimalRepository $animalRepository): Response
    {
        // Redirection pour les clients vers leur dashboard spécifique
        if ($this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('client_animaux');
        }

        $animals = $animalRepository->findAll();

        return $this->render('animal/index.html.twig', [
            'animals' => $animals,
        ]);
    }

    #[Route('/new', name: 'app_animal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CLIENT');

        $animal = new Animal();
        $animal->setProprietaire($this->getUser()); // Associer automatiquement le propriétaire

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de la photo
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/animaux',
                        $newFilename
                    );
                    $animal->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo : ' . $e->getMessage());
                }
            }

            $entityManager->persist($animal);
            $entityManager->flush();

            $this->addFlash('success', 'Animal créé avec succès !');
            return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal/new.html.twig', [
            'animal' => $animal,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_animal_show', methods: ['GET'])]
    public function show(Animal $animal): Response
    {
        // Vérifier si l'utilisateur a accès à cet animal
        if ($this->isGranted('ROLE_CLIENT') && !$this->isGranted('ROLE_ADMIN') && $animal->getProprietaire() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cet animal.');
        }

        return $this->render('animal/show.html.twig', [
            'animal' => $animal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_animal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Animal $animal, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est le propriétaire
        if ($animal->getProprietaire() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à modifier cet animal.');
        }

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de la photo
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                // Supprimer l'ancienne photo si elle existe
                $oldPhoto = $animal->getPhoto();
                if ($oldPhoto) {
                    $oldPhotoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/animaux/' . $oldPhoto;
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/animaux',
                        $newFilename
                    );
                    $animal->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo : ' . $e->getMessage());
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Animal modifié avec succès !');
            return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal/edit.html.twig', [
            'animal' => $animal,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_animal_delete', methods: ['POST'])]
    public function delete(Request $request, Animal $animal, EntityManagerInterface $entityManager): Response
    {
        // Vérifier si l'utilisateur est le propriétaire
        if ($animal->getProprietaire() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à supprimer cet animal.');
        }

        if ($this->isCsrfTokenValid('delete' . $animal->getId(), $request->request->get('_token'))) {
            // Supprimer la photo si elle existe
            $photo = $animal->getPhoto();
            if ($photo) {
                $photoPath = $this->getParameter('kernel.project_dir') . '/public/uploads/animaux/' . $photo;
                if (file_exists($photoPath)) {
                    unlink($photoPath);
                }
            }

            $entityManager->remove($animal);
            $entityManager->flush();

            $this->addFlash('success', 'Animal supprimé avec succès !');
        }

        return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
    }
}
