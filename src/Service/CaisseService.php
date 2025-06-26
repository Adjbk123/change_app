<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Devise;
use App\Entity\Caisse;
use App\Entity\CompteCaisse;
use App\Repository\AffectationCaisseRepository;
use App\Repository\CompteCaisseRepository;

class CaisseService
{
    private AffectationCaisseRepository $affectationCaisseRepository;
    private CompteCaisseRepository $compteCaisseRepository;

    public function __construct(
        AffectationCaisseRepository $affectationCaisseRepository,
        CompteCaisseRepository $compteCaisseRepository
    ) {
        $this->affectationCaisseRepository = $affectationCaisseRepository;
        $this->compteCaisseRepository = $compteCaisseRepository;
    }

    /**
     * Retourne la caisse actuellement affectée à l'utilisateur, ou null.
     */
    public function getCaisseAffectee(User $user): ?Caisse
    {
        $affectation = $this->affectationCaisseRepository->findActiveAffectationForUser($user);
        return $affectation ? $affectation->getCaisse() : null;
    }

    /**
     * Retourne le compte caisse actif pour une devise donnée, ou null.
     */
    public function getCompteCaisse(User $user, Devise $devise): ?CompteCaisse
    {
        $caisse = $this->getCaisseAffectee($user);
        if (!$caisse) return null;

        return $this->compteCaisseRepository->findOneBy([
            'caisse' => $caisse,
            'devise' => $devise
        ]);
    }

    /**
     * Vérifie si le solde en caisse est suffisant pour une opération.
     */
    public function hasSoldeSuffisant(User $user, Devise $devise, float $montant): bool
    {
        $compte = $this->getCompteCaisse($user, $devise);
        if (!$compte) return false;

        return $compte->getSoldeRestant() >= $montant;
    }
}
