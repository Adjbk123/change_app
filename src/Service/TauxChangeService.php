<?php
// src/Service/TauxChangeService.php

namespace App\Service;

use App\Entity\Devise;
use App\Entity\Agence;
use App\Repository\TauxChangeRepository;
use App\Repository\DeviseRepository;
use Doctrine\ORM\EntityManagerInterface;

class TauxChangeService
{
    private TauxChangeRepository $tauxChangeRepository;
    private DeviseRepository $deviseRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        TauxChangeRepository $tauxChangeRepository,
        DeviseRepository $deviseRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->tauxChangeRepository = $tauxChangeRepository;
        $this->deviseRepository = $deviseRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Récupère la devise pivot (XOF) du système.
     * @return Devise
     * @throws \Exception Si la devise pivot XOF est introuvable.
     */
    private function getPivotDevise(): Devise
    {
        $devisePivot = $this->deviseRepository->findOneBy(['codeIso' => 'XOF']);
        if (!$devisePivot) {
            throw new \Exception("La devise pivot XOF est introuvable en base de données.");
        }
        return $devisePivot;
    }

    /**
     * Calcule le taux de conversion nécessaire pour transformer un MontantSource en MontantCible.
     * C'est le taux (MontantCible / MontantSource).
     * S'inspire directement de la logique de calcul de taux de votre ApiController::getExchangeRate.
     *
     * @param Devise $deviseSource La devise du montant initial.
     * @param Devise $deviseCible La devise du montant converti.
     * @param Agence $agence L'agence concernée par les taux.
     * @return float Le taux de conversion (ex: 1 DeviseSource = X DeviseCible).
     * @throws \Exception Si aucun taux pertinent n'est trouvé.
     */
    public function getTaux(Devise $deviseSource, Devise $deviseCible, Agence $agence): float
    {
        // Si source et cible sont les mêmes, taux est 1
        if ($deviseSource->getId() === $deviseCible->getId()) {
            return 1.0;
        }

        $devisePivot = $this->getPivotDevise();

        $isSourcePivot = ($deviseSource->getId() === $devisePivot->getId());
        $isCiblePivot = ($deviseCible->getId() === $devisePivot->getId());

        // Cas 1: Conversion directe avec la devise pivot (XOF)
        if ($isSourcePivot || $isCiblePivot) {
            $deviseEtrangere = $isSourcePivot ? $deviseCible : $deviseSource;

            $tauxRecord = $this->tauxChangeRepository->findActiveRateForPair($deviseEtrangere, $devisePivot, $agence);

            // Si le taux étranger-pivot n'existe pas, on tente le pivot-étranger inverse
            if (!$tauxRecord) {
                $tauxRecord = $this->tauxChangeRepository->findActiveRateForPair($devisePivot, $deviseEtrangere, $agence);
                if ($tauxRecord) {
                    // On a le taux XOF -> Étranger, mais on veut Étranger -> XOF
                    // Utiliser 1 / taux_vente (pour avoir 1 Étranger = X XOF)
                    $tauxVente = (float) $tauxRecord->getTauxVente();
                    if ($tauxVente == 0) {
                        throw new \Exception("Taux de vente ($devisePivot->getCodeIso() -> $deviseEtrangere->getCodeIso()) est zéro pour une inversion.");
                    }
                    return 1 / $tauxVente; // 1 Étranger = 1 / TauxVente (XOF -> Étranger) XOF
                }
            }


            if (!$tauxRecord) {
                throw new \Exception("Taux non défini pour la paire " . $deviseSource->getCodeIso() . " / " . $deviseCible->getCodeIso() . " (direct avec pivot).");
            }

            if ($isSourcePivot) { // On va de XOF vers devise étrangère (VENTE pour agence)
                return (float) $tauxRecord->getTauxVente();
            } else { // On va de devise étrangère vers XOF (ACHAT pour agence)
                return (float) $tauxRecord->getTauxAchat();
            }
        } else {
            // Cas 2: Conversion croisée (ex: NGN -> USD, via XOF)
            // Agence achète la devise source avec XOF (taux_achat)
            $tauxSourceToPivotRecord = $this->tauxChangeRepository->findActiveRateForPair($deviseSource, $devisePivot, $agence);
            if (!$tauxSourceToPivotRecord) {
                throw new \Exception("Taux manquant pour conversion croisée: " . $deviseSource->getCodeIso() . " vers " . $devisePivot->getCodeIso() . ".");
            }
            $tauxAchatSource = (float) $tauxSourceToPivotRecord->getTauxAchat();

            // Agence vend la devise cible avec XOF (taux_vente)
            $tauxPivotToCibleRecord = $this->tauxChangeRepository->findActiveRateForPair($devisePivot, $deviseCible, $agence);
            if (!$tauxPivotToCibleRecord) {
                throw new \Exception("Taux manquant pour conversion croisée: " . $devisePivot->getCodeIso() . " vers " . $deviseCible->getCodeIso() . ".");
            }
            $tauxVenteCible = (float) $tauxPivotToCibleRecord->getTauxVente();

            if ($tauxVenteCible == 0) {
                throw new \Exception("Taux de vente ($devisePivot->getCodeIso() -> $deviseCible->getCodeIso()) est zéro pour une division.");
            }

            return $tauxAchatSource / $tauxVenteCible;
        }
    }

    /**
     * Convertit un montant d'une devise d'origine vers la devise principale (XOF) de l'agence.
     * Cette méthode est idéale pour la CONSOLIDATION de montants sur le tableau de bord.
     *
     * @param float $amount Le montant à convertir.
     * @param Devise $fromDevise La devise d'origine du montant.
     * @param Agence $agence L'agence pour obtenir les taux.
     * @return float Le montant converti en devise principale (XOF).
     * @throws \Exception Si la devise principale n'est pas trouvée ou si aucun taux n'est disponible.
     */
    public function convertToMainCurrency(float $amount, Devise $fromDevise, Agence $agence): float
    {
        $mainCurrency = $this->getPivotDevise(); // Utilise la devise pivot comme devise principale

        if ($fromDevise->getId() === $mainCurrency->getId()) {
            return $amount; // Pas de conversion si c'est déjà la devise principale
        }

        // Pour consolider "X" DeviseEtrangere en Y XOF, l'agence "achète" X DeviseEtrangere avec Y XOF.
        // Donc on utilise le taux d'achat (DeviseEtrangere -> XOF).
        // La méthode getTaux est déjà adaptée pour cela si la devise cible est XOF.

        $taux = $this->getTaux($fromDevise, $mainCurrency, $agence);
        return $amount * $taux;
    }
}
