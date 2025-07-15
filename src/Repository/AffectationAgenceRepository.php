<?php

namespace App\Repository;

use App\Entity\AffectationAgence;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AffectationAgence>
 */
class AffectationAgenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AffectationAgence::class);
    }

    //    /**
    //     * @return AffectationAgence[] Returns an array of AffectationAgence objects
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

    //    public function findOneBySomeField($value): ?AffectationAgence
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Retourne le responsable actif d'une agence (AffectationAgence)
     */
    public function findActiveResponsableByAgence($agence): ?AffectationAgence
    {
        $qb = $this->createQueryBuilder('a');
        $qb->where('a.agence = :agence')
            ->andWhere('a.roleInterne = :role')
            ->andWhere('a.actif = true')
            ->andWhere('a.dateDebut <= :now')
            ->andWhere($qb->expr()->orX('a.dateFin IS NULL', 'a.dateFin >= :now'))
            ->setParameter('agence', $agence)
            ->setParameter('role', 'ROLE_RESPONSABLE')
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->getOneOrNullResult();
    }

}
