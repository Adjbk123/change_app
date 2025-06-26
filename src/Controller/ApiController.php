<?php

namespace App\Controller;


use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\Devise;
use App\Entity\ProfilClient;
use App\Repository\AffectationCaisseRepository;
use App\Repository\CompteCaisseRepository;
use App\Repository\DeviseRepository;
use App\Repository\TauxChangeRepository;
use App\Service\CompteClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    #[Route('/api', name: 'app_api')]
    public function index(): Response
    {
        return $this->render('api/index.html.twig', [
            'controller_name' => 'ApiController',
        ]);
    }

    #[Route('/api/check-cash-desk-balance', name: 'api_check_cash_desk_balance', methods: ['POST'])]
    public function checkCashDeskBalance(
        Request $request,
        AffectationCaisseRepository $affectationCaisseRepository,
        CompteCaisseRepository $soldeCaisseRepository,
        DeviseRepository $deviseRepository
    ): JsonResponse {
        // 1. Récupérer l'utilisateur (caissier) connecté
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['sufficient' => false, 'message' => 'Utilisateur non authentifié.'], 401);
        }

        // 2. Trouver son affectation de caisse active
        $affectationActive = $affectationCaisseRepository->findActiveAffectationForUser($user);

        if (!$affectationActive) {
            return $this->json([
                'sufficient' => false,
                'message' => 'Aucune caisse active n\'est affectée à cet utilisateur.'
            ], 403);
        }

        $caisse = $affectationActive->getCaisse();

        // 3. Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        $deviseId = $data['devise'] ?? null;
        $montantRequis = (float)($data['montant'] ?? 0);

        // Si pas de montant ou de devise, pas de vérification à faire
        if (!$deviseId || $montantRequis <= 0) {
            return $this->json(['sufficient' => true]);
        }

        $devise = $deviseRepository->find($deviseId);
        if (!$devise) {
            return $this->json(['sufficient' => false, 'message' => 'Devise invalide.']);
        }

        // 4. Récupérer le solde de la caisse spécifique pour la devise demandée
        $soldeRecord = $soldeCaisseRepository->findOneBy([
            'caisse' => $caisse,
            'devise' => $devise
        ]);

        $soldeActuel = 0.0;
        if ($soldeRecord) {
            $soldeActuel = (float) $soldeRecord->getSoldeRestant();
        }

        // 5. Comparer le solde et retourner la réponse
        if ($soldeActuel >= $montantRequis) {
            return $this->json(['sufficient' => true]);
        } else {
            return $this->json([
                'sufficient' => false,
                'message' => 'Solde insuffisant dans la caisse pour cette devise.'
            ]);
        }
    }
    #[Route('/api/get-exchange-rate', name: 'api_get_exchange_rate', methods: ['POST'])]
    public function getExchangeRate(
        Request $request,
        TauxChangeRepository $tauxChangeRepository,
        DeviseRepository $deviseRepository,
        Security $security // Inject Security service to get user
    ): JsonResponse {
        $user = $security->getUser(); // Use Security service directly
        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non authentifié.'], 401);
        }

        $agence = $user->getAgence();
        if (!$agence) {
            return $this->json(['success' => false, 'message' => 'Utilisateur non affecté à une agence.'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $sourceId = $data['deviseSource'] ?? null;
        $cibleId = $data['deviseCible'] ?? null;

        $deviseSource = $deviseRepository->find($sourceId);
        $deviseCible = $deviseRepository->find($cibleId);

        if (!$deviseSource || !$deviseCible) {
            return $this->json(['success' => false, 'message' => 'Devises non valides.']);
        }

        $devisePivot = $deviseRepository->findOneBy(['codeIso' => 'XOF']);
        if (!$devisePivot) {
            return $this->json(['success' => false, 'message' => 'La devise pivot XOF est introuvable.']);
        }

        $tauxFinal = 0;
        $isPivot = false;
        $typeOperation = ''; // Initialize typeOperation

        // Determine if source or cible is the pivot currency (XOF)
        $isSourceXOF = ($deviseSource->getId() === $devisePivot->getId());
        $isCibleXOF = ($deviseCible->getId() === $devisePivot->getId());

        if ($isSourceXOF || $isCibleXOF) {
            // Direct exchange with XOF
            $deviseEtrangere = $isSourceXOF ? $deviseCible : $deviseSource;

            $tauxRecord = $tauxChangeRepository->findActiveRateForPair($deviseEtrangere, $devisePivot, $agence);
            if (!$tauxRecord) {
                return $this->json(['success' => false, 'message' => 'Taux non défini pour cette paire de devises.']);
            }

            if ($isSourceXOF) {
                // Client brings XOF, wants foreign currency -> Agency sells foreign (VENTE)
                $tauxFinal = (float) $tauxRecord->getTauxVente();
                $typeOperation = 'VENTE'; // Agency sells foreign currency
            } else {
                // Client brings foreign currency, wants XOF -> Agency buys foreign (ACHAT)
                $tauxFinal = (float) $tauxRecord->getTauxAchat();
                $typeOperation = 'ACHAT'; // Agency buys foreign currency
            }
        } else {
            // Cross-currency operation (neither is XOF)
            $isPivot = true;
            $typeOperation = 'ACHAT'; // Default for cross-currency: agency buys the source foreign currency from client

            $tauxSource = $tauxChangeRepository->findActiveRateForPair($deviseSource, $devisePivot, $agence);
            if (!$tauxSource) {
                return $this->json(['success' => false, 'message' => 'Taux manquant pour ' . $deviseSource->getCodeIso()]);
            }

            $tauxCible = $tauxChangeRepository->findActiveRateForPair($deviseCible, $devisePivot, $agence);
            if (!$tauxCible) {
                return $this->json(['success' => false, 'message' => 'Taux manquant pour ' . $deviseCible->getCodeIso()]);
            }

            $tauxAchatSource = (float) $tauxSource->getTauxAchat(); // Agency buys source from client (if it were against XOF)
            $tauxVenteCible = (float) $tauxCible->getTauxVente();   // Agency sells target to client (if it were against XOF)

            if ($tauxVenteCible == 0) {
                return $this->json(['success' => false, 'message' => 'Taux de vente de la devise cible invalide.']);
            }

            $tauxFinal = $tauxAchatSource / $tauxVenteCible;
        }

        return $this->json([
            'success' => true,
            'taux' => round($tauxFinal, 6),
            'isPivot' => $isPivot,
            'typeOperation' => $typeOperation // Include the operation type
        ]);
    }


    #[Route('/api/get-profil-clients-by-client', name: 'api_get_profil_clients_by_client', methods: ['POST'])]
    public function getProfilClientsByClient(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $clientId = $data['clientId'] ?? null;

        if (!$clientId) {
            return new JsonResponse(['success' => false, 'message' => 'ID client manquant.'], 400);
        }

        $client = $entityManager->getRepository(Client::class)->find($clientId);

        if (!$client) {
            return new JsonResponse(['success' => false, 'message' => 'Client introuvable.'], 404);
        }

        $profilClients = $entityManager->getRepository(ProfilClient::class)->findBy(['client' => $client]);

        $formattedProfilClients = [];
        foreach ($profilClients as $profilClient) {
            $formattedProfilClients[] = [
                'id' => $profilClient->getId(),
                'numeroProfilCompte' => $profilClient->getNumeroProfilCompte(),
                'typeClient' => $profilClient->getTypeClient() ? $profilClient->getTypeClient()->getLibelle() : 'N/A', // Assurez-vous que TypeClient a un getName() ou adaptez
            ];
        }

        return new JsonResponse([
            'success' => true,
            'profilClients' => $formattedProfilClients
        ]);
    }

    #[Route('/api/get-beneficiaires-by-profil-client', name: 'api_get_beneficiaires_by_profil_client', methods: ['POST'])]
    public function getBeneficiairesByProfilClient(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $profilClientId = $data['profilClientId'] ?? null;

        if (!$profilClientId) {
            return new JsonResponse(['success' => false, 'message' => 'ID de profil client manquant.'], 400);
        }

        $profilClient = $entityManager->getRepository(ProfilClient::class)->find($profilClientId);

        if (!$profilClient) {
            return new JsonResponse(['success' => false, 'message' => 'Profil client introuvable.'], 404);
        }

        // Récupérez tous les bénéficiaires associés à ce profil client
        $beneficiaires = $entityManager->getRepository(Beneficiaire::class)->findBy(['profilClient' => $profilClient], ['nomComplet' => 'ASC']);

        $formattedBeneficiaires = [];
        foreach ($beneficiaires as $beneficiaire) {
            $formattedBeneficiaires[] = [
                'id' => $beneficiaire->getId(),
                'nomComplet' => $beneficiaire->getNomComplet(),
                'contact' => $beneficiaire->getContact(),
                'typeCompte' => $beneficiaire->getTypeCompte(),
                'detailCompte' => $beneficiaire->getDetailCompte(),
                'institutionFinanciere' => $beneficiaire->getInstitutionFinanciere(),
                'pays' => $beneficiaire->getPays() ? [
                    'id' => $beneficiaire->getPays()->getId(),
                    'nom' => $beneficiaire->getPays()->getNom(), // Assurez-vous que votre entité Pays a une méthode getNom()
                ] : null,

            ];
        }

        return new JsonResponse([
            'success' => true,
            'beneficiaires' => $formattedBeneficiaires
        ]);
    }

    #[Route('/api/check-client-account-balance', name: 'api_check_client_account_balance', methods: ['POST'])]
    public function checkClientAccountBalance(
        Request $request,
        EntityManagerInterface $entityManager,
        CompteClientService $compteClientService // Injectez votre service CompteClientService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $profilClientId = $data['profilClientId'] ?? null;
        $deviseId = $data['deviseId'] ?? null;
        $montant = (float)($data['montant'] ?? 0);

        if (!$profilClientId || !$deviseId || $montant <= 0) {
            return new JsonResponse(['success' => false, 'sufficient' => false, 'message' => 'Données manquantes ou invalides (profil, devise, ou montant).'], 400);
        }

        $profilClient = $entityManager->getRepository(ProfilClient::class)->find($profilClientId);
        $devise = $entityManager->getRepository(Devise::class)->find($deviseId);

        if (!$profilClient || !$devise) {
            return new JsonResponse(['success' => false, 'sufficient' => false, 'message' => 'Profil client ou devise introuvable.'], 404);
        }

        // Récupérer le CompteClient (ou le créer s'il n'existe pas, bien qu'il ne devrait pas y avoir de retrait sans compte)
        $compteClient = $compteClientService->getOrCreateCompteClient($profilClient, $devise);

        // Vérifier si le solde actuel est suffisant pour le retrait
        $sufficient = $compteClient->getSoldeActuel() >= $montant;

        if ($sufficient) {
            return new JsonResponse(['success' => true, 'sufficient' => true, 'message' => 'Solde du compte client suffisant.']);
        } else {
            return new JsonResponse(['success' => true, 'sufficient' => false, 'message' => 'Solde insuffisant. Solde actuel: ' . $compteClient->getSoldeActuel() . ' ' . $devise->getCodeIso() . '.']);
        }
    }

}
