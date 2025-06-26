<?php

namespace App\Repository;

use App\Entity\Agence;
use App\Entity\Devise;
use App\Entity\TauxChange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TauxChange>
 */
class TauxChangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TauxChange::class);
    }
    /**
     * Trouve le taux actif pour une paire de devises donnée dans une agence,
     * quel que soit l'ordre des devises.
     */
    public function findActiveRateForPair(Devise $deviseA, Devise $deviseB, Agence $agence): ?TauxChange
    {
        $qb = $this->createQueryBuilder('t');

        $qb->where('t.agence = :agence')
            ->andWhere('t.isActif = :isActif')
            ->andWhere('t.dateDebut <= :now')
            ->andWhere($qb->expr()->orX(
                't.dateFin IS NULL',
                't.dateFin >= :now'
            ))
            // Gère la paire dans les deux sens (ex: USD/XOF ou XOF/USD)
            ->andWhere($qb->expr()->orX(
                $qb->expr()->andX('t.deviseSource = :deviseA', 't.deviseCible = :deviseB'),
                $qb->expr()->andX('t.deviseSource = :deviseB', 't.deviseCible = :deviseA')
            ))
            ->setParameter('agence', $agence)
            ->setParameter('isActif', true)
            ->setParameter('now', new \DateTime())
            ->setParameter('deviseA', $deviseA)
            ->setParameter('deviseB', $deviseB)
            ->orderBy('t.dateDebut', 'DESC') // Prend le taux le plus récent en cas de doublon
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
    //    /**
    //     * @return TauxChange[] Returns an array of TauxChange objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TauxChange
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
