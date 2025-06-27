<?php

namespace App\Repository;

use App\Entity\Caisse;
use App\Entity\CompteCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompteCaisse>
 */
class CompteCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompteCaisse::class);
    }

    /**
     * Récupère tous les comptes de caisse pour une caisse donnée.
     */
    public function findByCaisse(Caisse $caisse): array
    {
        return $this->createQueryBuilder('cc')
            ->where('cc.caisse = :caisse')
            ->setParameter('caisse', $caisse)
            ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return CompteCaisse[] Returns an array of CompteCaisse objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?CompteCaisse
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
