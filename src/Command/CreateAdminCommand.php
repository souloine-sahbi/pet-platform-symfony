<?php

namespace App\Command;

use App\Entity\Admin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur',
)]
class CreateAdminCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $admin = new Admin();
        $admin->setNom('Admin');
        $admin->setPrenom('Super');
        $admin->setEmail('admin@petnexus.com');
        $admin->setTelephone('+216 12 345 678');
        $admin->setAdresse('Tunis, Tunisie');

        // Hashphp bin/console make:entity Evaluation du mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'Admin' // Mot de passe par défaut
        );
        $admin->setPassword($hashedPassword);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        $io->success('Administrateur créé avec succès !');
        $io->table(
            ['Champ', 'Valeur'],
            [
                ['Email', 'admin@petnexus.com'],
                ['Mot de passe', 'Admin'],
                ['Nom', 'Super Admin'],
            ]
        );

        return Command::SUCCESS;
    }
}
