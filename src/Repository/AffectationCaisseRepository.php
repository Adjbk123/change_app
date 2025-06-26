<?php

namespace App\Repository;

use App\Entity\AffectationCaisse;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffectationCaisse>
 */
class AffectationCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectationCaisse::class);
    }

    public function findActiveAffectationForUser(User $user): ?AffectationCaisse
    {
        $qb = $this->createQueryBuilder('ac');

        $qb->where('ac.caissier = :user')
            ->andWhere('ac.isActive = :estActif')
            ->andWhere('ac.dateDebut <= :now')
            ->andWhere($qb->expr()->orX(
                'ac.dateFin IS NULL',
                'ac.dateFin >= :now'
            ))
            ->setParameter('user', $user)
            ->setParameter('estActif', true)
            ->setParameter('now', new \DateTime('now'))
            ->orderBy('ac.dateDebut', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }


    //    /**
    //     * @return AffectationCaisse[] Returns an array of AffectationCaisse objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?AffectationCaisse
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
