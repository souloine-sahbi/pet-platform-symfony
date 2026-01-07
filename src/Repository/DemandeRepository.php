<?php
// src/Repository/DemandeRepository.php

namespace App\Repository;

use App\Entity\Demande;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DemandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Demande::class);
    }

    /**
     * Trouve toutes les demandes d'un utilisateur (envoyées ou reçues)
     */
    public function findByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.demandeur = :user OR d.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('d.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes envoyées par un utilisateur
     */
    public function findSentByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.demandeur = :user')
            ->setParameter('user', $user)
            ->orderBy('d.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes reçues par un utilisateur
     */
    public function findReceivedByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('d.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les demandes en attente pour un utilisateur
     */
    public function findPendingForUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.destinataire = :user')
            ->andWhere('d.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', 'en_attente')
            ->orderBy('d.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
