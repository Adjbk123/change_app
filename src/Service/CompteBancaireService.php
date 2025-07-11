<?php

namespace App\Service;

use App\Entity\CompteBancaire; // Assurez-vous que le namespace de votre entité est correct
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class CompteBancaireService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Crédite un compte bancaire avec le montant spécifié.
     *
     * @param CompteBancaire $compte Le compte bancaire à créditer.
     * @param float $montant Le montant à ajouter au solde.
     * @throws Exception Si le montant est négatif ou nul.
     */
    public function crediter(CompteBancaire $compte, float $montant): void
    {
        if ($montant <= 0) {
            throw new Exception("Le montant à créditer doit être supérieur à zéro.");
        }

        $compte->setSoldeInitial($compte->getSoldeInitial() + $montant);
        $compte->setSoldeRestant($compte->getSoldeRestant() + $montant);
        $this->entityManager->persist($compte);
        // Note: Le flush est fait dans le contrôleur pour s'assurer que toutes les opérations sont atomiques.
    }

    /**
     * Débite un compte bancaire avec le montant spécifié.
     *
     * @param CompteBancaire $compte Le compte bancaire à débiter.
     * @param float $montant Le montant à retirer du solde.
     * @throws Exception Si le montant est négatif ou nul, ou si le solde est insuffisant.
     */
    public function debiter(CompteBancaire $compte, float $montant): void
    {
        if ($montant <= 0) {
            throw new Exception("Le montant à débiter doit être supérieur à zéro.");
        }

        if ($compte->getSoldeRestant() < $montant) {
            throw new Exception("Solde insuffisant sur le compte bancaire " . $compte->getNumeroBancaire() . ".");
        }

        $compte->setSoldeRestant($compte->getSoldeRestant() - $montant);
        $this->entityManager->persist($compte);
        // Note: Le flush est fait dans le contrôleur pour s'assurer que toutes les opérations sont atomiques.
    }

    // Vous pouvez ajouter d'autres méthodes ici si nécessaire, par exemple :
    // public function transferer(CompteBancaire $source, CompteBancaire $destination, float $montant): void
    // public function getCompteBancaireByDevise(User $agent, Devise $devise): ?CompteBancaire
}
