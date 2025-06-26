<?php

namespace App\Repository;

use App\Entity\CompteClient;
use App\Entity\Devise;
use App\Entity\ProfilClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompteClient>
 */
class CompteClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompteClient::class);
    }

    public function findOneByProfilClientAndDevise(ProfilClient $profilClient, Devise $devise): ?CompteClient // MODIFIÉ
    {
        return $this->createQueryBuilder('cc')
            ->andWhere('cc.profilClient = :profilClient') // MODIFIÉ
            ->andWhere('cc.devise = :devise')
            ->setParameter('profilClient', $profilClient) // MODIFIÉ
            ->setParameter('devise', $devise)
            ->getQuery()
            ->getOneOrNullResult();
    }

    //    /**
    //     * @return CompteClient[] Returns an array of CompteClient objects
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

    //    public function findOneBySomeField($value): ?CompteClient
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
