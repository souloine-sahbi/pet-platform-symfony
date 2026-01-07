<?php

namespace App\Repository;

use App\Entity\Message;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Trouve tous les messages d'un utilisateur
     */
    public function findByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages envoyés par un utilisateur
     */
    public function findSentByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.expediteur = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les messages reçus par un utilisateur
     */
    public function findReceivedByUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve la conversation entre deux utilisateurs
     */
    public function findConversation(int $userId1, int $userId2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.expediteur = :user1 AND m.destinataire = :user2) OR (m.expediteur = :user2 AND m.destinataire = :user1)')
            ->setParameter('user1', $userId1)
            ->setParameter('user2', $userId2)
            ->orderBy('m.dateEnvoi', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les conversations d'un utilisateur
     */
    public function findConversations(Utilisateur $user): array
    {
        // Récupère les derniers messages de chaque conversation
        $qb = $this->createQueryBuilder('m');

        return $qb->select('m')
            ->where('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les messages non lus pour un utilisateur
     */
    public function countUnreadMessages(Utilisateur $user): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.destinataire = :user')
            ->andWhere('m.lu = false OR m.lu IS NULL')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve les contacts avec qui l'utilisateur a échangé
     */
    public function findContacts(Utilisateur $user): array
    {
        $qb = $this->createQueryBuilder('m');

        return $qb->select('DISTINCT u.id, u.prenom, u.nom, u.email')
            ->join('m.expediteur', 'e')
            ->join('m.destinataire', 'd')
            ->where('m.expediteur = :user OR m.destinataire = :user')
            ->setParameter('user', $user)
            ->addSelect('CASE WHEN e.id = :user THEN d ELSE e END as contact')
            ->addOrderBy('m.dateEnvoi', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function countMessagesToday(): int
    {
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.dateEnvoi >= :today')
            ->andWhere('m.dateEnvoi < :tomorrow')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
