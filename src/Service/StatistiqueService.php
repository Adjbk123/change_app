<?php
// src/Service/StatistiqueService.php

namespace App\Service;

use App\Entity\Agence;
use App\Entity\Caisse;
use App\Entity\Devise;
use App\Repository\CompteAgenceRepository;
use App\Repository\CompteCaisseRepository;
use App\Repository\DeviseRepository;
use App\Repository\OperationRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

class StatistiqueService
{
    private CompteCaisseRepository $compteCaisseRepository;
    private OperationRepository $operationRepository;
    private DeviseRepository $deviseRepository;
    private TauxChangeService $tauxChangeService;
    private EntityManagerInterface $entityManager;
    private CompteAgenceRepository $compteAgenceRepository;

    public function __construct(
        CompteCaisseRepository $compteCaisseRepository,
        OperationRepository $operationRepository,
        DeviseRepository $deviseRepository,
        TauxChangeService $tauxChangeService,
        EntityManagerInterface $entityManager,
        CompteAgenceRepository $compteAgenceRepository
    ) {
        $this->compteCaisseRepository = $compteCaisseRepository;
        $this->operationRepository = $operationRepository;
        $this->deviseRepository = $deviseRepository;
        $this->tauxChangeService = $tauxChangeService;
        $this->entityManager = $entityManager;
        $this->compteAgenceRepository = $compteAgenceRepository;
    }

    /**
     * Récupère les statistiques détaillées par devise pour une caisse donnée pour la journée en cours.
     */
    public function getStatsParCaisse(Caisse $caisse): array
    {
        $today = new DateTimeImmutable();
        $startOfDay = $today->setTime(0, 0, 0);
        $endOfDay = $today->setTime(23, 59, 59);

        // Devise principale de référence pour l'affichage (ex: pour les montants nuls)
        $mainCurrency = $this->deviseRepository->findOneBy(['codeIso' => 'XOF']);
        $mainCurrencyCode = $mainCurrency ? $mainCurrency->getCodeIso() : 'XOF';

        // 1. Solde actuel par devise
        $currentCashBalanceByCurrency = [];
        $comptesCaisses = $this->compteCaisseRepository->findByCaisse($caisse);
        foreach ($comptesCaisses as $compteCaisse) {
            $deviseCode = $compteCaisse->getDevise()->getCodeIso();
            $currentCashBalanceByCurrency[$deviseCode] = (float)$compteCaisse->getSoldeRestant();
        }

        // --- Initialisation des tableaux de résultats par devise ---
        $dailyReceivedByCurrency = []; // Dépôts clients + Montant reçu des ventes de devises
        $dailyGivenByCurrency = [];    // Retraits clients + Montant donné des achats de devises
        $dailyBuyVolumeByCurrency = [];  // Volume d'achat (ce que le client a donné)
        $dailySellVolumeByCurrency = []; // Volume de vente (ce que l'agence a donné)
        $dailyRatesUsed = [];

        // Fonction utilitaire pour agréger les résultats des requêtes par devise
        $aggregateByCurrency = function (array $data, array &$targetArray) {
            foreach ($data as $item) {
                // On récupère l'objet Devise pour obtenir son code ISO
                $devise = $this->entityManager->getRepository(Devise::class)->find($item['deviseId']);
                if ($devise) {
                    $deviseCode = $devise->getCodeIso();
                    $targetArray[$deviseCode] = ($targetArray[$deviseCode] ?? 0.0) + (float)$item['totalMontant'];
                }
            }
        };

        // 2. Montant total reçu aujourd’hui (entrées) - par devise
        // Dépôts clients + ce que la caisse a reçu lors des ventes de devises (montant source)
        $depositsData = $this->operationRepository->getSumMontantCibleByCaisseAndType($caisse, 'DEPOT', $startOfDay, $endOfDay);
        $aggregateByCurrency($depositsData, $dailyReceivedByCurrency);
        $salesSourceData = $this->operationRepository->getSumMontantSourceForVenteByCaisse($caisse, $startOfDay, $endOfDay);
        $aggregateByCurrency($salesSourceData, $dailyReceivedByCurrency);

        // 3. Montant total sorti aujourd’hui (sorties) - par devise
        // Retraits clients + ce que la caisse a donné lors des achats de devises (montant cible)
        $withdrawalsData = $this->operationRepository->getSumMontantCibleByCaisseAndType($caisse, 'RETRAIT_ESPECES', $startOfDay, $endOfDay);
        $aggregateByCurrency($withdrawalsData, $dailyGivenByCurrency);
        $purchaseTargetData = $this->operationRepository->getSumMontantCibleForAchatByCaisse($caisse, $startOfDay, $endOfDay);
        $aggregateByCurrency($purchaseTargetData, $dailyGivenByCurrency);

        // 4. Volume des opérations d'achat/vente par devise
        // Montant Acheté (en devise source) -> ce que le client donne
        $purchaseSourceData = $this->operationRepository->getSumMontantSourceForAchatByCaisse($caisse, $startOfDay, $endOfDay);
        $aggregateByCurrency($purchaseSourceData, $dailyBuyVolumeByCurrency);

        // Montant Vendu (en devise cible) -> ce que l'agence donne
        $salesTargetData = $this->operationRepository->getSumMontantCibleForVenteByCaisse($caisse, $startOfDay, $endOfDay);
        $aggregateByCurrency($salesTargetData, $dailySellVolumeByCurrency);

        // 6. Alertes de seuils faibles
        $alerts = [];
        foreach ($comptesCaisses as $compteCaisse) {
            if ($compteCaisse->getSoldeRestant() < $compteCaisse->getSeuilAlerte()) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => "Solde faible en " . $compteCaisse->getDevise()->getCodeIso() . " : " . number_format((float)$compteCaisse->getSoldeRestant(), 2, ',', ' ') . " (seuil : " . number_format((float)$compteCaisse->getSeuilAlerte(), 2, ',', ' ') . ").",
                    'deviseCode' => $compteCaisse->getDevise()->getCodeIso()
                ];
            }
        }

        // 7. Taux utilisés aujourd’hui
        $usedRates = $this->operationRepository->getDailyUsedExchangeRatesByCaisse($caisse, $startOfDay, $endOfDay);
        foreach ($usedRates as $rate) {
            $sourceDevise = $this->deviseRepository->find($rate['sourceId']);
            $cibleDevise = $this->deviseRepository->find($rate['cibleId']);
            if ($sourceDevise && $cibleDevise) {
                $dailyRatesUsed[] = [
                    'deviseSourceCode' => $sourceDevise->getCodeIso(),
                    'deviseCibleCode' => $cibleDevise->getCodeIso(),
                    'taux' => (float)$rate['taux'],
                    'typeOperation' => $rate['typeOperation'] // ACHAT ou VENTE
                ];
            }
        }

        // 8. Préparation des données pour les graphiques
        $allCurrenciesForChart = array_unique(array_merge(
            array_keys($dailyReceivedByCurrency),
            array_keys($dailyGivenByCurrency)
        ));
        sort($allCurrenciesForChart);

        $receivedData = [];
        $givenData = [];
        foreach ($allCurrenciesForChart as $currency) {
            $receivedData[] = $dailyReceivedByCurrency[$currency] ?? 0.0;
            $givenData[] = $dailyGivenByCurrency[$currency] ?? 0.0;
        }

        $chartData = [
            'entriesExits' => [
                'labels' => $allCurrenciesForChart,
                'datasets' => [
                    [
                        'name' => 'Total Reçu',
                        'data' => $receivedData,
                    ],
                    [
                        'name' => 'Total Sorti',
                        'data' => $givenData,
                    ],
                ]
            ]
        ];

        // Structure de retour finale
        return [
            'currentCashBalanceByCurrency' => $currentCashBalanceByCurrency,
            'dailyReceivedByCurrency' => $dailyReceivedByCurrency,
            'dailyGivenByCurrency' => $dailyGivenByCurrency,
            'dailyBuyVolumeByCurrency' => $dailyBuyVolumeByCurrency,
            'dailySellVolumeByCurrency' => $dailySellVolumeByCurrency,
            'dailyRatesUsed' => $dailyRatesUsed,
            'alerts' => $alerts,
            'mainCurrencyCode' => $mainCurrencyCode,
            'chartData' => $chartData, // Données formatées pour les graphiques
        ];
    }

    /**
     * Récupère les statistiques agrégées pour toutes les caisses d'une agence pour la journée en cours.
     */
    public function getStatsParAgence(Agence $agence): array
    {
        $today = new DateTimeImmutable();
        $startOfDay = $today->setTime(0, 0, 0);
        $endOfDay = $today->setTime(23, 59, 59);

        $mainCurrency = $this->deviseRepository->findOneBy(['codeIso' => 'XOF']);
        $mainCurrencyCode = $mainCurrency ? $mainCurrency->getCodeIso() : 'XOF';

        $aggregateByCurrency = function (array $data, array &$targetArray) {
            foreach ($data as $item) {
                $devise = $this->entityManager->getRepository(Devise::class)->find($item['deviseId']);
                if ($devise) {
                    $deviseCode = $devise->getCodeIso();
                    $targetArray[$deviseCode] = ($targetArray[$deviseCode] ?? 0.0) + (float)$item['totalMontant'];
                }
            }
        };

        // --- CALCULS AGRÉGÉS POUR L'AGENCE ---

        // 1. Solde actuel par devise (Caisses + Comptes de l'Agence)
        $currentCashBalanceByCurrency = [];
        // Soldes des caisses
        $comptesCaisses = $this->compteCaisseRepository->findByAgence($agence); // On suppose que cette méthode existe
        foreach ($comptesCaisses as $compteCaisse) {
            $deviseCode = $compteCaisse->getDevise()->getCodeIso();
            $currentCashBalanceByCurrency[$deviseCode] = ($currentCashBalanceByCurrency[$deviseCode] ?? 0.0) + (float)$compteCaisse->getSoldeRestant();
        }
        // Soldes des comptes de l'agence
        $comptesAgence = $this->compteAgenceRepository->findBy(['agence' => $agence]);
        foreach ($comptesAgence as $compte) {
            $deviseCode = $compte->getDevise()->getCodeIso();
            $currentCashBalanceByCurrency[$deviseCode] = ($currentCashBalanceByCurrency[$deviseCode] ?? 0.0) + (float)$compte->getSoldeRestant();
        }

        // --- Initialisation des tableaux ---
        $dailyReceivedByCurrency = [];
        $dailyGivenByCurrency = [];
        $dailyBuyVolumeByCurrency = [];
        $dailySellVolumeByCurrency = [];
        $dailyRatesUsed = [];

        // 2. Montant total reçu aujourd’hui (agrégé pour l'agence)
        $depositsData = $this->operationRepository->getSumMontantCibleByAgenceAndType($agence, 'DEPOT', $startOfDay, $endOfDay);
        $aggregateByCurrency($depositsData, $dailyReceivedByCurrency);
        $salesSourceData = $this->operationRepository->getSumMontantSourceForVenteByAgence($agence, $startOfDay, $endOfDay);
        $aggregateByCurrency($salesSourceData, $dailyReceivedByCurrency);

        // 3. Montant total sorti aujourd’hui (agrégé pour l'agence)
        $withdrawalsData = $this->operationRepository->getSumMontantCibleByAgenceAndType($agence, 'RETRAIT_ESPECES', $startOfDay, $endOfDay);
        $aggregateByCurrency($withdrawalsData, $dailyGivenByCurrency);
        $purchaseTargetData = $this->operationRepository->getSumMontantCibleForAchatByAgence($agence, $startOfDay, $endOfDay);
        $aggregateByCurrency($purchaseTargetData, $dailyGivenByCurrency);

        // 4. Volume des opérations d'achat/vente (agrégé pour l'agence)
        $purchaseSourceData = $this->operationRepository->getSumMontantSourceForAchatByAgence($agence, $startOfDay, $endOfDay);
        $aggregateByCurrency($purchaseSourceData, $dailyBuyVolumeByCurrency);
        $salesTargetData = $this->operationRepository->getSumMontantCibleForVenteByAgence($agence, $startOfDay, $endOfDay);
        $aggregateByCurrency($salesTargetData, $dailySellVolumeByCurrency);

        // 6. Alertes de seuils faibles (pour toutes les caisses de l'agence)
        $alerts = [];
        foreach ($comptesCaisses as $compteCaisse) {
            if ($compteCaisse->getSoldeRestant() < $compteCaisse->getSeuilAlerte()) {
                $alerts[] = [
                    'type' => 'danger',
                    'message' => "Solde faible pour la caisse '{$compteCaisse->getCaisse()->getLibelle()}' en {$compteCaisse->getDevise()->getCodeIso()} : " . number_format((float)$compteCaisse->getSoldeRestant(), 2, ',', ' ') . " (seuil : " . number_format((float)$compteCaisse->getSeuilAlerte(), 2, ',', ' ') . ").",
                    'deviseCode' => $compteCaisse->getDevise()->getCodeIso()
                ];
            }
        }

        // 7. Taux utilisés aujourd’hui (dans toute l'agence)
        $usedRates = $this->operationRepository->getDailyUsedExchangeRatesByAgence($agence, $startOfDay, $endOfDay);
        // ... (la logique de formatage des taux est identique à getStatsParCaisse)
        foreach ($usedRates as $rate) {
            $sourceDevise = $this->deviseRepository->find($rate['sourceId']);
            $cibleDevise = $this->deviseRepository->find($rate['cibleId']);
            if ($sourceDevise && $cibleDevise) {
                $dailyRatesUsed[] = [
                    'deviseSourceCode' => $sourceDevise->getCodeIso(),
                    'deviseCibleCode' => $cibleDevise->getCodeIso(),
                    'taux' => (float)$rate['taux'],
                    'typeOperation' => $rate['typeOperation']
                ];
            }
        }

        // 8. Préparation des données pour les graphiques (avec les données agrégées)
        $allCurrenciesForChart = array_unique(array_merge(
            array_keys($dailyReceivedByCurrency),
            array_keys($dailyGivenByCurrency)
        ));
        sort($allCurrenciesForChart);

        $receivedData = [];
        $givenData = [];
        foreach ($allCurrenciesForChart as $currency) {
            $receivedData[] = $dailyReceivedByCurrency[$currency] ?? 0.0;
            $givenData[] = $dailyGivenByCurrency[$currency] ?? 0.0;
        }

        $chartData = [
            'entriesExits' => [
                'labels' => $allCurrenciesForChart,
                'datasets' => [
                    ['name' => 'Total Reçu', 'data' => $receivedData],
                    ['name' => 'Total Sorti', 'data' => $givenData],
                ]
            ]
        ];

        // Structure de retour finale (identique à getStatsParCaisse)
        return [
            'currentCashBalanceByCurrency' => $currentCashBalanceByCurrency,
            'dailyReceivedByCurrency' => $dailyReceivedByCurrency,
            'dailyGivenByCurrency' => $dailyGivenByCurrency,
            'dailyBuyVolumeByCurrency' => $dailyBuyVolumeByCurrency,
            'dailySellVolumeByCurrency' => $dailySellVolumeByCurrency,
            'dailyRatesUsed' => $dailyRatesUsed,
            'alerts' => $alerts,
            'mainCurrencyCode' => $mainCurrencyCode,
            'chartData' => $chartData,
        ];
    }

    /**
     * @return array
     * @TODO Implémenter la logique pour les statistiques globales (toutes agences)
     */
    public function getGlobalStats(): array
    {
        // La logique serait d'agréger les données de toutes les agences
        return ['message' => 'Statistiques globales à implémenter.'];
    }
}
