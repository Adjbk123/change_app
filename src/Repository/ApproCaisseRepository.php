<?php

namespace App\Repository;

use App\Entity\Agence;
use App\Entity\ApproCaisse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApproCaisse>
 */
class ApproCaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApproCaisse::class);
    }
    public function findByAgence(Agence $agence): array
    {
        return $this->createQueryBuilder('ac') // 'ac' est un alias pour ApproCaisse
        ->join('ac.caisse', 'c')          // Jointure avec l'entité Caisse, alias 'c'
        ->where('c.agence = :agence')     // Condition : l'agence de la caisse doit être celle passée en paramètre
        ->setParameter('agence', $agence) // Lier le paramètre pour la sécurité
        ->orderBy('ac.createdAt', 'DESC') // Trier par date de création, du plus récent au plus ancien
        ->getQuery()
            ->getResult();
    }
    //    /**
    //     * @return ApproCaisse[] Returns an array of ApproCaisse objects
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

    //    public function findOneBySomeField($value): ?ApproCaisse
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
