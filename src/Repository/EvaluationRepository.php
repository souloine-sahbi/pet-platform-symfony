<?php

namespace App\Repository;

use App\Entity\Evaluation;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evaluation::class);
    }

    /**
     * Compter le nombre total d'évaluations
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Calculer la moyenne générale de toutes les évaluations
     */
    public function getAverageNote(): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('AVG(e.note)')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round($result, 2) : 0;
    }

    /**
     * Obtenir la moyenne des notes pour un utilisateur spécifique
     */
    public function getAverageNoteForUser(Client $client): float
    {
        $result = $this->createQueryBuilder('e')
            ->select('AVG(e.note)')
            ->where('e.evalue = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? round($result, 2) : 0;
    }

    /**
     * Obtenir toutes les évaluations pour un utilisateur
     */
    public function findByEvalue(Client $client)
    {
        return $this->createQueryBuilder('e')
            ->where('e.evalue = :client')
            ->setParameter('client', $client)
            ->orderBy('e.dateEvaluation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtenir les 5 utilisateurs les mieux notés
     */
    public function getTopRatedUsers(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->select('IDENTITY(e.evalue) as user_id, AVG(e.note) as average_note, COUNT(e.id) as total_evaluations')
            ->groupBy('e.evalue')
            ->orderBy('average_note', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les évaluations reçues par un utilisateur
     */
    public function countForUser(Client $client): int
    {
        return $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.evalue = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
