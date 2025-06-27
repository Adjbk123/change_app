<?php
// src/Repository/OperationRepository.php

namespace App\Repository;

use App\Entity\Agence;
use App\Entity\Operation;
use App\Entity\Caisse;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Operation>
 */
class OperationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operation::class);
    }

    /**
     * Calcule la somme des montants cibles d'opérations (DEPOT, RETRAIT_ESPECES) pour une caisse, groupé par devise cible.
     * @return array[] {['totalMontant': float, 'deviseId': int]}
     */
    public function getSumMontantCibleByCaisseAndType(Caisse $caisse, string $typeOperation, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantCible) AS totalMontant, IDENTITY(o.deviseCible) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.id = :caisseId')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('caisseId', $caisse->getId())
            ->setParameter('typeOperation', $typeOperation)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Calcule la somme des montants source pour les opérations d'ACHAT pour une caisse, groupé par devise source.
     * C'est ce que la caisse a REÇU lors d'un achat client.
     * @return array[] {['totalMontant': float, 'deviseId': int]}
     */
    public function getSumMontantSourceForAchatByCaisse(Caisse $caisse, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantSource) AS totalMontant, IDENTITY(o.deviseSource) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.id = :caisseId')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('caisseId', $caisse->getId())
            ->setParameter('typeOperation', 'ACHAT')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Calcule la somme des montants cible pour les opérations de VENTE pour une caisse, groupé par devise cible.
     * C'est ce que la caisse a DONNÉ lors d'une vente client.
     * @return array[] {['totalMontant': float, 'deviseId': int]}
     */
    public function getSumMontantCibleForVenteByCaisse(Caisse $caisse, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantCible) AS totalMontant, IDENTITY(o.deviseCible) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.id = :caisseId')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('caisseId', $caisse->getId())
            ->setParameter('typeOperation', 'VENTE')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * NOUVELLE MÉTHODE : Calcule la somme des montants SOURCE pour les opérations de VENTE pour une caisse, groupé par devise source.
     * C'est ce que la caisse a REÇU lors d'une vente client (client donne deviseSource).
     * @return array[] {['totalMontant': float, 'deviseId': int]}
     */
    public function getSumMontantSourceForVenteByCaisse(Caisse $caisse, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantSource) AS totalMontant, IDENTITY(o.deviseSource) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.id = :caisseId')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('caisseId', $caisse->getId())
            ->setParameter('typeOperation', 'VENTE') // Type d'opération 'VENTE'
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }


    /**
     * NOUVELLE MÉTHODE : Calcule la somme des montants CIBLE pour les opérations d'ACHAT pour une caisse, groupé par devise cible.
     * C'est ce que la caisse a DONNÉ lors d'un achat client (l'agence achète la source et donne la cible).
     * @return array[] {['totalMontant': float, 'deviseId': int]}
     */
    public function getSumMontantCibleForAchatByCaisse(Caisse $caisse, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantCible) AS totalMontant, IDENTITY(o.deviseCible) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.id = :caisseId')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('caisseId', $caisse->getId())
            ->setParameter('typeOperation', 'ACHAT') // Type d'opération 'ACHAT'
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }


    /**
     * Récupère la liste des taux de change uniques utilisés pour les opérations d'achat/vente aujourd'hui par une caisse.
     * @return array[] {['sourceId': int, 'cibleId': int, 'taux': float, 'typeOperation': string]}
     */
    public function getDailyUsedExchangeRatesByCaisse(Caisse $caisse, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('IDENTITY(o.deviseSource) AS sourceId, IDENTITY(o.deviseCible) AS cibleId, o.taux, o.typeOperation')
            ->join('o.caisse', 'c')
            ->join('o.deviseSource', 'ds')
            ->join('o.deviseCible', 'dc')
            ->where('c.id = :caisseId')
            ->andWhere('o.typeOperation IN (:types)')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('caisseId', $caisse->getId())
            ->setParameter('types', ['ACHAT', 'VENTE'])
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('sourceId', 'cibleId', 'o.taux', 'o.typeOperation') // Grouper par paire, taux et type pour obtenir les taux uniques
            ->getQuery()
            ->getArrayResult();
    }



    // --- NOUVELLES MÉTHODES PAR AGENCE ---

    /**
     * Calcule la somme des montants cibles pour un type d'opération donné, pour toutes les caisses d'une agence.
     */
    public function getSumMontantCibleByAgenceAndType(Agence $agence, string $typeOperation, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantCible) AS totalMontant, IDENTITY(o.deviseCible) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.agence = :agence')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('agence', $agence)
            ->setParameter('typeOperation', $typeOperation)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Calcule la somme des montants SOURCE pour les opérations de VENTE pour toutes les caisses d'une agence.
     */
    public function getSumMontantSourceForVenteByAgence(Agence $agence, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantSource) AS totalMontant, IDENTITY(o.deviseSource) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.agence = :agence')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('agence', $agence)
            ->setParameter('typeOperation', 'VENTE')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Calcule la somme des montants CIBLE pour les opérations d'ACHAT pour toutes les caisses d'une agence.
     */
    public function getSumMontantCibleForAchatByAgence(Agence $agence, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantCible) AS totalMontant, IDENTITY(o.deviseCible) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.agence = :agence')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('agence', $agence)
            ->setParameter('typeOperation', 'ACHAT')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Calcule la somme des montants SOURCE pour les opérations d'ACHAT pour toutes les caisses d'une agence.
     */
    public function getSumMontantSourceForAchatByAgence(Agence $agence, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantSource) AS totalMontant, IDENTITY(o.deviseSource) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.agence = :agence')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('agence', $agence)
            ->setParameter('typeOperation', 'ACHAT')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Calcule la somme des montants CIBLE pour les opérations de VENTE pour toutes les caisses d'une agence.
     */
    public function getSumMontantCibleForVenteByAgence(Agence $agence, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('SUM(o.montantCible) AS totalMontant, IDENTITY(o.deviseCible) AS deviseId')
            ->join('o.caisse', 'c')
            ->where('c.agence = :agence')
            ->andWhere('o.typeOperation = :typeOperation')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('agence', $agence)
            ->setParameter('typeOperation', 'VENTE')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('deviseId')
            ->getQuery()
            ->getArrayResult();
    }

    /**
     * Récupère les taux de change uniques utilisés par toutes les caisses d'une agence.
     */
    public function getDailyUsedExchangeRatesByAgence(Agence $agence, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array
    {
        return $this->createQueryBuilder('o')
            ->select('IDENTITY(o.deviseSource) AS sourceId, IDENTITY(o.deviseCible) AS cibleId, o.taux, o.typeOperation')
            ->join('o.caisse', 'c')
            ->where('c.agence = :agence')
            ->andWhere('o.typeOperation IN (:types)')
            ->andWhere('o.createdAt BETWEEN :startDate AND :endDate')
            ->setParameter('agence', $agence)
            ->setParameter('types', ['ACHAT', 'VENTE'])
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->groupBy('sourceId', 'cibleId', 'o.taux', 'o.typeOperation')
            ->getQuery()
            ->getArrayResult();
    }
}
