<?php

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function countUnreadForUserAndDiscussion($user, $discussion)
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.discussion = :discussion')
            ->andWhere('m.auteur != :user')
            ->andWhere('m.readAt IS NULL')
            ->setParameter('discussion', $discussion)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
} 