<?php

namespace App\Repository;

use App\Entity\Session;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Session::class);
    }

    /**
     * Trouve toutes les sessions d'un utilisateur
     */
    public function findByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.demande', 'd')
            ->where('d.demandeur = :user OR d.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('s.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sessions actives
     */
    public function findActiveSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.statut = :statut')
            ->setParameter('statut', 'en_cours')
            ->orderBy('s.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sessions terminées
     */
    public function findCompletedSessions(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.statut = :statut')
            ->setParameter('statut', 'terminee')
            ->orderBy('s.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les sessions pour une demande spécifique
     */
    public function findByDemandeId(int $demandeId): ?Session
    {
        return $this->createQueryBuilder('s')
            ->join('s.demande', 'd')
            ->where('d.id = :demandeId')
            ->setParameter('demandeId', $demandeId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Statistiques des sessions
     */
    public function getStats(): array
    {
        $qb = $this->createQueryBuilder('s')
            ->select('COUNT(s.id) as total')
            ->addSelect('SUM(CASE WHEN s.statut = :en_cours THEN 1 ELSE 0 END) as en_cours')
            ->addSelect('SUM(CASE WHEN s.statut = :terminee THEN 1 ELSE 0 END) as terminee')
            ->addSelect('SUM(CASE WHEN s.statut = :annulee THEN 1 ELSE 0 END) as annulee')
            ->setParameter('en_cours', 'en_cours')
            ->setParameter('terminee', 'terminee')
            ->setParameter('annulee', 'annulee');

        return $qb->getQuery()->getSingleResult();
    }
    public function countSessionsByUser(Utilisateur $user): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.demande', 'd')
            ->where('d.demandeur = :user OR d.destinataire = :user')
            ->andWhere('s.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('statut', 'en_cours')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
