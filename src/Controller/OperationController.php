<?php

namespace App\Controller;

use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\CompteBancaire;
use App\Entity\Devise;
use App\Entity\MouvementCaisse;
use App\Entity\MouvementCompteBancaire;
use App\Entity\MouvementCompteClient;
use App\Entity\Operation;
use App\Entity\Pays;
use App\Entity\PieceIdentite;
use App\Entity\ProfilClient;
use App\Entity\TypeClient;
use App\Form\OperationForm;
use App\Repository\CompteBancaireRepository;
use App\Repository\OperationRepository;
use App\Service\CaisseService;
use App\Service\CompteBancaireService;
use App\Service\CompteClientService;
use App\Service\PdfService;
use App\Service\QrCodeGeneratorService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/operation')]
final class OperationController extends AbstractController
{
    #[Route(name: 'app_operation_index', methods: ['GET'])]
    public function index(OperationRepository $operationRepository, CaisseService $caisseService): Response
    {
        $operations =[];
        if ($this->isGranted('ROLE_CAISSE')) {
            $caisse= $caisseService->getCaisseAffectee($this->getUser());
            $operations= $operationRepository->findBy(['caisse'=>$caisse]);
        }
        return $this->render('operation/index.html.twig', [
            'operations' => $operationRepository->findAll(),
        ]);
    }
    #[Route('/transferts', name: 'app_transfert_index', methods: ['GET'])]
    public function indexTransferts(OperationRepository $operationRepository): Response
    {
        // On récupère toutes les opérations de type 'RETRAIT_TRANSFERT_EN_ATTENTE' et 'RETRAIT_TRANSFERT_FINALISE'
        // ou 'DEPOT_BANCAIRE' si vous considérez les dépôts bancaires comme des transferts à suivre ici.
        // Je vais me baser sur les types de retrait transfert que nous avons définis.

        $transferOperations = $operationRepository->findBy([
            'typeOperation' => ['RETRAIT_TRANSFERT_EN_ATTENTE', 'RETRAIT_TRANSFERT']
            // Si vous voulez inclure les dépôts bancaires ici:
            // 'typeOperation' => ['RETRAIT_TRANSFERT_EN_ATTENTE', 'RETRAIT_TRANSFERT_FINALISE', 'DEPOT_BANCAIRE']
        ], ['createdAt' => 'DESC']); // Tri par date de création décroissante

        return $this->render('operation/transfert_index.html.twig', [
            'operations' => $transferOperations,
        ]);
    }

    #[Route('/operation-transfert/{id}', name: 'app_operation_show_transfert', methods: ['GET'])]
    public function showTransfert(
        Operation $operation,
        Request $request,
        CompteBancaireRepository $compteBancaireRepository // Injectez le repository
    ): Response {
        $action = $request->query->get('action');
        $comptesBancaires = [];
        $showFinalisationForm = false;

        // Si l'action est de finaliser un transfert
        if ($action === 'finaliser_transfert' &&
            $operation->getTypeOperation() === 'RETRAIT_TRANSFERT_EN_ATTENTE' &&
            !$operation->isFinalise())       {
            $showFinalisationForm = true;
            // Récupérer les comptes bancaires disponibles de la bonne devise
            $comptesBancaires = $compteBancaireRepository->findBy(['devise' => $operation->getDeviseCible()]);
            // Ou tous les comptes si la devise peut être choisie dans le formulaire de finalisation
            // $comptesBancaires = $compteBancaireRepository->findAll();

            if (empty($comptesBancaires)) {
                $this->addFlash('warning', 'Aucun compte bancaire disponible dans la devise ' . $operation->getDeviseCible()->getCodeIso() . ' pour finaliser ce transfert.');
                $showFinalisationForm = false; // Ne pas afficher le formulaire si pas de compte
            }
        }

        return $this->render('operation/showTransfert.html.twig', [
            'operation' => $operation,
            'showFinalisationForm' => $showFinalisationForm,
            'comptesBancaires' => $comptesBancaires,
        ]);
    }
    #[Route('/impression', name: 'app_operation_print', methods: ['GET'])]
    public function printPdf(Request $request): Response
    {
        $pdfPath = $request->query->get('pdfPath'); // Récupère le chemin du PDF depuis l'URL

        if (!$pdfPath) {
            $this->addFlash('danger', 'Le chemin du PDF est manquant.');
            return $this->redirectToRoute('app_dashboard'); // Redirigez vers une page par défaut
        }


        if (!str_starts_with($pdfPath, 'uploads/recu/') || str_contains($pdfPath, '..')) {
            $this->addFlash('danger', 'Chemin de fichier invalide.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Passe le chemin du PDF à la vue
        return $this->render('operation/print_pdf.html.twig', [
            'pdf_path' => '/' . $pdfPath,
        ]);
    }


    #[Route('/new/{type}', name: 'app_operation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        string $type,
        CompteBancaireRepository $compteBancaireRepository
    ): Response {

        $clients = $entityManager->getRepository(Client::class)->findBy([], ['nom' => 'ASC']);
        $devises = $entityManager->getRepository(Devise::class)->findAll();

        // Données pour le formulaire de la MODALE
        $typeClients = $entityManager->getRepository(TypeClient::class)->findAll();
        $pieceIdentiteTypes = $entityManager->getRepository(PieceIdentite::class)->findAll();
        $pays = $entityManager->getRepository(Pays::class)->findAll();

        return $this->render('operation/new.html.twig', [
            'clients' => $clients,
            'devises' => $devises,
            'typeClients' => $typeClients,
            'pieceIdentiteTypes' => $pieceIdentiteTypes,
            'type' => $type,
            'pays' => $pays,
            "comptesBancairesAgence" => $compteBancaireRepository->findAll(),
        ]);
    }

    #[Route('/achat-vente/store', name: 'operation_achat_vente_new', methods: ['POST'])]
    public function storeAchatVente(
        Request $request,
        EntityManagerInterface $entityManager,
        CaisseService $caisseService,
        PdfService $pdfService,               // Injectez PdfService
        QrCodeGeneratorService $qrCodeService // Injectez QrCodeGeneratorService

    ): Response {
        if (!$this->isCsrfTokenValid('new_operation_token', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_operation_new', ['type' => 'achat_vente']); // Spécifiez le type pour la redirection
        }

        $data = $request->request->all();
        $user = $this->getUser();

        // Création de l'opération
        $operation = new Operation();
        $operation->setTypeOperation($data['typeOperation']);
        $operation->setCreatedAt(new DateTimeImmutable());
        $operation->setAgent($user);

        // Récupération des entités liées
        $client = $entityManager->getRepository(Client::class)->find($data['client'] ?? null);
        $deviseSource = $entityManager->getRepository(Devise::class)->find($data['deviseSource'] ?? null);
        $deviseCible = $entityManager->getRepository(Devise::class)->find($data['deviseCible'] ?? null);

        if ($client) {
            $operation->setClient($client);
            // Si l'opération d'achat/vente n'a pas de profil client direct, on peut le chercher via le client
            $profilClient = $entityManager->getRepository(ProfilClient::class)->findOneBy(['client' => $client]);
            if ($profilClient) {
                $operation->setProfilClient($profilClient); // Lier le profil client si trouvé
            }
        }
        if ($deviseSource) $operation->setDeviseSource($deviseSource);
        if ($deviseCible) $operation->setDeviseCible($deviseCible);

        $montantSource = $data['montantSource'] ?? null;
        $montantCible = $data['montantCible'] ?? null;
        $taux = $data['taux'] ?? null;

        $operation->setMontantSource($montantSource);
        $operation->setMontantCible($montantCible);
        $operation->setTaux($taux);
        $operation->setCaisse($caisseService->getCaisseAffectee($this->getUser()));

        // Déterminer le sens (entrée ou sortie)
        $sens = ($data['typeOperation'] === "ACHAT") ? "ENTREE" : "SORTIE";
        $operation->setSens($sens);


        // Mouvement 1 : Devise Source (ce que le client donne à l'agence)
        $compteCaisseSource = $caisseService->getCompteCaisse($user, $deviseSource);
        if (!$compteCaisseSource) {
            $this->addFlash('danger', "Aucun compte caisse disponible pour la devise source " . $deviseSource->getCodeIso());
            return $this->redirectToRoute('app_operation_new', ['type' => 'achat_vente']);
        }

        $mouvementSource = new MouvementCaisse();
        $mouvementSource->setCompteCaisse($compteCaisseSource);
        $mouvementSource->setDateMouvement(new DateTimeImmutable());

        // Mouvement 2 : Devise Cible (ce que l'agence donne au client)
        $compteCaisseCible = $caisseService->getCompteCaisse($user, $deviseCible);
        if (!$compteCaisseCible) {
            $this->addFlash('danger', "Aucun compte caisse disponible pour la devise cible " . $deviseCible->getCodeIso());
            return $this->redirectToRoute('app_operation_new', ['type' => 'achat_vente']);
        }

        $mouvementCible = new MouvementCaisse();
        $mouvementCible->setCompteCaisse($compteCaisseCible);
        $mouvementCible->setDateMouvement(new DateTimeImmutable());

        if ($data['typeOperation'] === "ACHAT") {
            // L'agence ACHETE la deviseSource du client (deviseSource ENTRE, deviseCible SORT)
            $mouvementSource->setTypeMouvement("ENTREE");
            $mouvementSource->setMontant($montantSource);

            $mouvementCible->setTypeMouvement("SORTIE");
            $mouvementCible->setMontant($montantCible);

            // Vérifier solde caisse cible
            if ($compteCaisseCible->getSoldeRestant() < $montantCible) {
                $this->addFlash('danger', "Solde caisse insuffisant en " . $deviseCible->getCodeIso() . " pour cet achat.");
                return $this->redirectToRoute('app_operation_new', ['type' => 'achat_vente']);
            }

            $compteCaisseSource->setSoldeRestant($compteCaisseSource->getSoldeRestant() - $montantSource);
            $compteCaisseCible->setSoldeRestant($compteCaisseCible->getSoldeRestant() + $montantCible);

            $operation->setSens("ENTREE"); // Sens de l'opération du point de vue de l'agence (reçoit deviseSource)

        } else { // VENTE
            // L'agence VEND la deviseCible au client (deviseSource SORT, deviseCible ENTRE)
            $mouvementSource->setTypeMouvement("SORTIE");
            $mouvementSource->setMontant($montantSource);

            $mouvementCible->setTypeMouvement("ENTREE");
            $mouvementCible->setMontant($montantCible);

            // Vérifier solde caisse source
            if ($compteCaisseSource->getSoldeRestant() < $montantSource) {
                $this->addFlash('danger', "Solde caisse insuffisant en " . $deviseSource->getCodeIso() . " pour cette vente.");
                return $this->redirectToRoute('app_operation_new', ['type' => 'achat_vente']);
            }

            $compteCaisseSource->setSoldeRestant($compteCaisseSource->getSoldeRestant() + $montantSource);
            $compteCaisseCible->setSoldeRestant($compteCaisseCible->getSoldeRestant() - $montantCible);

            $operation->setSens("SORTIE"); // Sens de l'opération du point de vue de l'agence (donne deviseCible)
        }

        // Enregistrement
        $entityManager->persist($operation);
        $entityManager->persist($mouvementSource);
        $entityManager->persist($mouvementCible); // Persistez les deux mouvements
        $entityManager->flush();

        $this->addFlash('success', 'Opération enregistrée avec succès !');

        // --- Génération PDF et QR Code ---
        $qrCodeData = "Operation Ref: " . $operation->getId() .
            " | Type: " . $operation->getTypeOperation() .
            " | Montant: " . $operation->getMontantSource() . " " . $operation->getDeviseSource()->getCodeIso() .
            " | Agent: " . $operation->getAgent()->getNomComplet() .
            " | Date: " . $operation->getCreatedAt()->format('Y-m-d H:i:s');

        $qrCodeBase64 = $qrCodeService->generateQrCodeBase64($qrCodeData);

        // Définir le nom du fichier PDF
        $filename = sprintf('recu_operation_%s_%s.pdf', $operation->getTypeOperation(), $operation->getId());

        // Rendre le template Twig pour le PDF avec les données nécessaires
        $pdfPath = $pdfService->savePdf('operation/recu.html.twig', [
            'operation' => $operation,
            'qr_code' => $qrCodeBase64,
        ], $filename, 227,350);
        // Rediriger vers la nouvelle route d'impression avec le chemin du PDF
        return $this->redirectToRoute('app_operation_print', ['pdfPath' => $pdfPath]);

    }
    #[Route('/versement/store', name: 'operation_versement_new', methods: ['POST'])]
    public function storeVersement(
        Request $request,
        EntityManagerInterface $entityManager,
        CaisseService $caisseService,
        CompteClientService $compteClientService,
        PdfService $pdfService,
        QrCodeGeneratorService $qrCodeService,
        CompteBancaireService $compteBancaireService, // NOUVEAU : Injectez le service pour les comptes bancaires de l'agence
        CompteBancaireRepository $compteBancaireRepository // NOUVEAU : Injectez le service pour les comptes bancaires de l'agence
    ): Response {
        if (!$this->isCsrfTokenValid('new_operation_token', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
        }

        $data = $request->request->all();

        $user = $this->getUser();

        $profilClientId = $data['profilClient'] ?? null;
        $profilClient = $entityManager->getRepository(ProfilClient::class)->find($profilClientId);

        $typeDepot = $data['typeDepot'] ?? 'classique'; // NOUVEAU: Récupérer le type de dépôt (ex: 'classique', 'banque')

        $deviseApportee = $entityManager->getRepository(Devise::class)->find($data['deviseApportee'] ?? null);
        $montantApporte = (float)($data['montantApporte'] ?? 0);
        $deviseCibleCompte = $entityManager->getRepository(Devise::class)->find($data['deviseCibleCompte'] ?? null);

        if (!$profilClient || !$deviseApportee || !$deviseCibleCompte || $montantApporte <= 0) {
            $this->addFlash('danger', 'Veuillez fournir toutes les informations nécessaires pour le dépôt.');
            return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
        }

        try {
            $entityManager->beginTransaction();

            // NOUVEAU CAS : Dépôt par virement bancaire
            if ($typeDepot === 'bancaire') {
                $compteBancaireAgenceId = $data['compteBancaireAgence'] ?? null;
                $compteBancaireAgence = $compteBancaireRepository->find($compteBancaireAgenceId);
                $referenceBancaire = $data['referenceBancaire'] ?? null;
                $dateValeur = isset($data['dateValeur']) ? new DateTimeImmutable($data['dateValeur']) : new DateTimeImmutable();


                if (!$compteBancaireAgence) {
                    throw new \Exception("Veuillez sélectionner le compte bancaire de l'agence.");
                }
                // Ici, pas de conversion prévue dans ce cas précis (client dépose X, son compte est crédité de X)
                if ($deviseApportee->getId() !== $deviseCibleCompte->getId()) {
                    throw new \Exception("Les devises pour un dépôt bancaire doivent être identiques (pas de conversion via banque directe).");
                }

                $operation = new Operation();
                $operation->setTypeOperation('DEPOT_BANCAIRE'); // Type spécifique
                $operation->setCreatedAt(new DateTimeImmutable());
                $operation->setAgent($user);
                $operation->setDeviseSource($deviseApportee);
                $operation->setMontantSource($montantApporte);
                $operation->setDeviseCible($deviseCibleCompte);
                $operation->setMontantCible($montantApporte); // Montant identique car pas de conversion
                $operation->setTaux(1.0);
                $operation->setCaisse(null); // Pas de mouvement de caisse physique pour ce type d'opération
                $operation->setSens("ENTREE");
                $operation->setProfilClient($profilClient);

                // Lier l'opération au CompteClient cible
                $compteClient = $compteClientService->getOrCreateCompteClient($profilClient, $deviseApportee);
                $operation->setCompteClientCible($compteClient);

                // NOUVEAU : Enregistrer le mouvement sur le compte bancaire de l'agence
                $mouvementBancaireAgence = new MouvementCompteBancaire(); // Supposons une entité MouvementCompteBancaireAgence
                $mouvementBancaireAgence->setCompteBancaire($compteBancaireAgence);
                $mouvementBancaireAgence->setTypeMouvement('DEPOT');
                $mouvementBancaireAgence->setMontant($montantApporte);
                $mouvementBancaireAgence->setDateMouvement(new DateTimeImmutable()); // Date du mouvement dans le système
                $mouvementBancaireAgence->setDateValeur($dateValeur); // Date effective sur le relevé bancaire
                $mouvementBancaireAgence->setReferenceBancaire($referenceBancaire);
                $entityManager->persist($mouvementBancaireAgence);

                // Mettre à jour le solde du compte bancaire de l'agence (via service)
                $compteBancaireService->crediter($compteBancaireAgence, $montantApporte);
                $entityManager->persist($compteBancaireAgence); // Persister la mise à jour du solde

                // Créditer le CompteClient
                $compteClientService->deposer($compteClient, $montantApporte);

                // Création du MouvementCompteClient
                $mouvementCompteClient = new MouvementCompteClient();
                $mouvementCompteClient->setCompteClient($compteClient);
                $mouvementCompteClient->setTypeMouvement('DEPOT_BANCAIRE');
                $mouvementCompteClient->setSens('CREDIT');
                $mouvementCompteClient->setMontant($montantApporte);
                $mouvementCompteClient->setDateMouvement(new \DateTime());
                $entityManager->persist($mouvementCompteClient);

                $entityManager->persist($operation);
                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Dépôt par virement bancaire enregistré avec succès !');

                // --- Génération PDF et QR Code ---
                $qrCodeData = "Operation Ref: " . $operation->getId() .
                    " | Type: " . $operation->getTypeOperation() .
                    " | Client: " . $profilClient->getClient()->getNomComplet() .
                    " | Montant: " . $operation->getMontantCible() . " " . $operation->getDeviseCible()->getCodeIso() .
                    " | Via Banque: " . $compteBancaireAgence->getNomBanque() . " (" . $compteBancaireAgence->getNumeroCompte() . ")";

                $qrCodeBase64 = $qrCodeService->generateQrCodeBase64($qrCodeData);

                $filename = sprintf('recu_depot_bancaire_%s_%s.pdf', $profilClient->getClient()->getNom(), $operation->getId());

                return $pdfService->streamPdf('operation/recu_depot_bancaire.html.twig', [ // NOUVEAU template PDF
                    'operation' => $operation,
                    'qr_code' => $qrCodeBase64,
                    'compte_bancaire_agence' => $compteBancaireAgence,
                    'reference_bancaire' => $referenceBancaire,
                    'date_valeur' => $dateValeur,
                ], $filename);

            } // FIN NOUVEAU CAS BANCAIRE
            else if ($deviseApportee->getId() === $deviseCibleCompte->getId()) {
                // ... (Votre code existant pour le dépôt classique)
                $operation = new Operation();
                $operation->setTypeOperation('DEPOT');
                $operation->setCreatedAt(new DateTimeImmutable());
                $operation->setAgent($user);
                $operation->setDeviseSource($deviseApportee);
                $operation->setMontantSource($montantApporte);
                $operation->setDeviseCible($deviseCibleCompte);
                $operation->setMontantCible($montantApporte);
                $operation->setTaux(1.0); // Taux de 1 car pas de conversion
                $operation->setCaisse($caisseService->getCaisseAffectee($user));
                $operation->setSens("ENTREE");
                $operation->setProfilClient($profilClient); // Lier le profil client à l'opération

                // Récupère le CompteClient pour le dépôt
                $compteClient = $compteClientService->getOrCreateCompteClient($profilClient, $deviseApportee);
                $operation->setCompteClientCible($compteClient); // Lier l'opération au CompteClient cible

                $compteCaisse = $caisseService->getCompteCaisse($user, $deviseApportee);
                if (!$compteCaisse) {
                    throw new \Exception("Aucun compte caisse disponible pour la devise " . $deviseApportee->getCodeIso());
                }

                // Mouvement de caisse (ENTREE)
                $mouvementCaisse = new MouvementCaisse();
                $mouvementCaisse->setCompteCaisse($compteCaisse);
                $mouvementCaisse->setTypeMouvement("ENTREE");
                $mouvementCaisse->setMontant($montantApporte);
                $mouvementCaisse->setDateMouvement(new DateTimeImmutable());
                $entityManager->persist($mouvementCaisse);

                $compteCaisse->setSoldeRestant($compteCaisse->getSoldeRestant() + $montantApporte);
                $entityManager->persist($compteCaisse);

                // Créditer le CompteClient
                $compteClientService->deposer($compteClient, $montantApporte);

                // Création du MouvementCompteClient
                $mouvementCompteClient = new MouvementCompteClient();
                $mouvementCompteClient->setCompteClient($compteClient);
                $mouvementCompteClient->setTypeMouvement('DEPOT');
                $mouvementCompteClient->setSens('CREDIT');
                $mouvementCompteClient->setMontant($montantApporte);
                $mouvementCompteClient->setDateMouvement(new \DateTime());
                $entityManager->persist($mouvementCompteClient);

                $entityManager->persist($operation);
                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Dépôt classique enregistré avec succès !');

                // --- Génération PDF et QR Code ---
                $qrCodeData = "Operation Ref: " . $operation->getId() .
                    " | Type: " . $operation->getTypeOperation() .
                    " | Client: " . $profilClient->getClient()->getNomComplet() .
                    " | Montant: " . $operation->getMontantCible() . " " . $operation->getDeviseCible()->getCodeIso();

                $qrCodeBase64 = $qrCodeService->generateQrCodeBase64($qrCodeData);

                $filename = sprintf('recu_depot_%s_%s.pdf', $profilClient->getClient()->getNom(), $operation->getId());

                return $pdfService->streamPdf('operation/recu.html.twig', [
                    'operation' => $operation,
                    'qr_code' => $qrCodeBase64,
                ], $filename);
            } else {
                // ... (Votre code existant pour le dépôt avec conversion)
                $taux = (float)($data['tauxDepot'] ?? 0);
                $montantConverti = (float)($data['montantCibleCompte'] ?? 0);

                if ($taux <= 0 || $montantConverti <= 0) {
                    throw new \Exception('Erreur de calcul du taux ou du montant converti. Veuillez réessayer.');
                }

                $operationAchat = new Operation();
                $operationAchat->setTypeOperation('DEPOT_AVEC_CONVERSION'); // Type d'opération plus spécifique
                $operationAchat->setCreatedAt(new DateTimeImmutable());
                $operationAchat->setAgent($user);
                $operationAchat->setDeviseSource($deviseApportee);
                $operationAchat->setMontantSource($montantApporte);
                $operationAchat->setDeviseCible($deviseCibleCompte);
                $operationAchat->setMontantCible($montantConverti);
                $operationAchat->setTaux($taux);
                $operationAchat->setCaisse($caisseService->getCaisseAffectee($user));
                $operationAchat->setSens("ENTREE"); // L'argent apporté entre en caisse (DeviseSource)
                $operationAchat->setProfilClient($profilClient); // Lier le profil client à l'opération

                // Récupère le CompteClient pour le dépôt final
                $compteClient = $compteClientService->getOrCreateCompteClient($profilClient, $deviseCibleCompte);
                $operationAchat->setCompteClientCible($compteClient); // Lier l'opération au CompteClient crédité

                // Mouvement de caisse pour la devise apportée (ENTREE)
                $compteCaisseApportee = $caisseService->getCompteCaisse($user, $deviseApportee);
                if (!$compteCaisseApportee) {
                    throw new \Exception("Aucun compte caisse disponible pour la devise apportée " . $deviseApportee->getCodeIso());
                }
                $mouvementApporteeCaisse = new MouvementCaisse();
                $mouvementApporteeCaisse->setCompteCaisse($compteCaisseApportee);
                $mouvementApporteeCaisse->setTypeMouvement("ENTREE");
                $mouvementApporteeCaisse->setMontant($montantApporte);
                $mouvementApporteeCaisse->setDateMouvement(new DateTimeImmutable());
                $entityManager->persist($mouvementApporteeCaisse);

                $compteCaisseApportee->setSoldeRestant($compteCaisseApportee->getSoldeRestant() + $montantApporte);
                $entityManager->persist($compteCaisseApportee);

                // Mouvement de caisse pour la devise cible (SORTIE)
                $compteCaisseCible = $caisseService->getCompteCaisse($user, $deviseCibleCompte);
                if (!$compteCaisseCible) {
                    throw new \Exception("Aucun compte caisse disponible pour la devise cible " . $deviseCibleCompte->getCodeIso());
                }
                if ($compteCaisseCible->getSoldeRestant() < $montantConverti) {
                    throw new \Exception("Solde caisse insuffisant en " . $deviseCibleCompte->getCodeIso() . " pour la conversion.");
                }
                $mouvementCibleCaisse = new MouvementCaisse();
                $mouvementCibleCaisse->setCompteCaisse($compteCaisseCible);
                $mouvementCibleCaisse->setTypeMouvement("SORTIE");
                $mouvementCibleCaisse->setMontant($montantConverti);
                $mouvementCibleCaisse->setDateMouvement(new DateTimeImmutable());
                $entityManager->persist($mouvementCibleCaisse);

                $compteCaisseCible->setSoldeRestant($compteCaisseCible->getSoldeRestant() - $montantConverti);
                $entityManager->persist($compteCaisseCible);

                $entityManager->persist($operationAchat);

                // Créditer le compte client avec le montant converti
                $compteClientService->deposer($compteClient, $montantConverti);

                // Création du MouvementCompteClient pour la conversion
                $mouvementCompteClient = new MouvementCompteClient();
                $mouvementCompteClient->setCompteClient($compteClient);
                $mouvementCompteClient->setTypeMouvement('DEPOT_CONVERSION'); // Type spécifique
                $mouvementCompteClient->setMontant($montantConverti);
                $mouvementCompteClient->setSens('CREDIT');
                $mouvementCompteClient->setDateMouvement(new \DateTime());
                $entityManager->persist($mouvementCompteClient);

                $entityManager->flush();
                $entityManager->commit();

                $this->addFlash('success', 'Dépôt avec conversion enregistré avec succès !');

                // --- Génération PDF et QR Code ---
                $qrCodeData = "Operation Ref: " . $operationAchat->getId() .
                    " | Type: " . $operationAchat->getTypeOperation() .
                    " | Client: " . $profilClient->getClient()->getNomComplet() .
                    " | Montant Apporté: " . $operationAchat->getMontantSource() . " " . $operationAchat->getDeviseSource()->getCodeIso() .
                    " | Montant Crédité: " . $operationAchat->getMontantCible() . " " . $operationAchat->getDeviseCible()->getCodeIso();

                $qrCodeBase64 = $qrCodeService->generateQrCodeBase64($qrCodeData);

                $filename = sprintf('recu_depot_conversion_%s_%s.pdf', $profilClient->getClient()->getNom(), $operationAchat->getId());


                // Rendre le template Twig pour le PDF avec les données nécessaires
                $pdfPath = $pdfService->savePdf('operation/recu.html.twig', [
                    'operation' => $operationAchat,
                    'qr_code' => $qrCodeBase64,
                ], $filename, 227,350);
                // Rediriger vers la nouvelle route d'impression avec le chemin du PDF
                return $this->redirectToRoute('app_operation_print', ['pdfPath' => $pdfPath]);
            }
        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement du versement : ' . $e->getMessage());
            return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
        }
    }

    #[Route('/retrait/store', name: 'operation_retrait_new', methods: ['POST'])]
    public function storeRetrait(
        Request $request,
        EntityManagerInterface $entityManager,
        CaisseService $caisseService,
        CompteClientService $compteClientService,
        PdfService $pdfService,               // Injectez PdfService
        QrCodeGeneratorService $qrCodeService // Injectez QrCodeGeneratorService

    ): Response {
        // 1. Vérification du jeton CSRF
        if (!$this->isCsrfTokenValid('new_operation_token', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
        }

        $data = $request->request->all();
        $user = $this->getUser(); // L'agent effectuant l'opération

        // 2. Récupération des entités de base
        $profilClientId = $data['profilClient'] ?? null;
        $profilClient = $entityManager->getRepository(ProfilClient::class)->find($profilClientId);
        $deviseCompteADebiter = $entityManager->getRepository(Devise::class)->find($data['deviseCompteADebiter'] ?? null);
        $montantRetrait = (float)($data['montantRetrait'] ?? 0);
        $typeRetrait = $data['typeRetrait'] ?? null; // ESPECES ou TRANSFERT
        $deviseADonner = $entityManager->getRepository(Devise::class)->find($data['deviseADonner'] ?? null); // Devise finalement donnée

        // 3. Validation initiale des données critiques
        if (!$profilClient || !$deviseCompteADebiter || $montantRetrait <= 0 || !$typeRetrait || !$deviseADonner) {
            $this->addFlash('danger', 'Veuillez fournir toutes les informations nécessaires pour le retrait.');
            return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
        }

        // Récupération du CompteClient à débiter
        $compteClientADebiter = $compteClientService->getOrCreateCompteClient($profilClient, $deviseCompteADebiter);

        // **Validation serveur du solde du compte client (CRUCIAL)**
        // Bien que le JS fasse une vérification, le serveur doit toujours valider.
        if ($compteClientADebiter->getSoldeActuel() < $montantRetrait) {
            $this->addFlash('danger', 'Solde insuffisant sur le compte client pour ce retrait.');
            return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
        }

        try {
            $entityManager->beginTransaction(); // Démarre une transaction pour assurer l'atomicité

            // Débit du compte client (MouvementCompteClient)
            $compteClientService->retirer($compteClientADebiter, $montantRetrait);

            $montantADonner = (float)($data['montantADonner'] ?? 0);
            $tauxRetrait = (float)($data['tauxRetrait'] ?? 1); // Taux est 1 si pas de conversion

            // Vérifier que les montants et taux envoyés sont cohérents si conversion
            $isConversion = ($deviseCompteADebiter->getId() !== $deviseADonner->getId());
            if ($isConversion && ($tauxRetrait <= 0 || $montantADonner <= 0)) {
                $this->addFlash('danger', 'Erreur de calcul du taux ou du montant à donner pour la conversion.');
                $entityManager->rollback();
                return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
            }
            // Si pas de conversion, s'assurer que montantADonner est bien montantRetrait
            if (!$isConversion) {
                $montantADonner = $montantRetrait;
            }


            // Création de l'opération principale
            $operation = new Operation();
            $operation->setCreatedAt(new DateTimeImmutable());
            $operation->setAgent($user);
            $operation->setTypeOperation("RETRAIT"); // Sera mis à jour plus bas
            $operation->setClient($profilClient->getClient());
            $operation->setProfilClient($profilClient); // Lier le profil client à l'opération
            $operation->setCompteClientSource($compteClientADebiter);
            $operation->setDeviseSource($deviseCompteADebiter); // Devise débitée du compte client
            $operation->setMontantSource($montantRetrait);
            $operation->setDeviseCible($deviseADonner);     // Devise donnée (ou transférée)
            $operation->setMontantCible($montantADonner);
            $operation->setTaux($isConversion ? $tauxRetrait : 1.0); // Enregistrer le taux si conversion
            $operation->setCaisse($caisseService->getCaisseAffectee($user)); // Caisse de l'agent


            // Création du mouvement de compte client (Débit)
            $mouvementCompteClient = new MouvementCompteClient();
            $mouvementCompteClient->setCompteClient($compteClientADebiter);
            $mouvementCompteClient->setMontant($montantRetrait);
            $mouvementCompteClient->setSens("DEBIT");
            $mouvementCompteClient->setDateMouvement(new \DateTime());

            // Logique spécifique selon le type de retrait
            if ($typeRetrait === 'ESPECES') {
                $operation->setTypeOperation('RETRAIT_ESPECES');
                $operation->setSens('SORTIE'); // L'argent sort de la caisse

                // Mouvement de caisse (SORTIE)
                $compteCaisseADebiter = $caisseService->getCompteCaisse($user, $deviseADonner);
                if (!$compteCaisseADebiter) {
                    throw new \Exception("Aucun compte caisse disponible pour la devise " . $deviseADonner->getCodeIso());
                }
                // Vérification du solde de caisse (CRUCIAL pour espèces)
                if ($compteCaisseADebiter->getSoldeRestant() < $montantADonner) {
                    throw new \Exception("Solde caisse insuffisant en " . $deviseADonner->getCodeIso() . " pour ce retrait.");
                }

                $mouvementCaisse = new MouvementCaisse();
                $mouvementCaisse->setCompteCaisse($compteCaisseADebiter);
                $mouvementCaisse->setTypeMouvement("SORTIE");
                $mouvementCaisse->setMontant($montantADonner);
                $mouvementCaisse->setDateMouvement(new DateTimeImmutable());
                $entityManager->persist($mouvementCaisse);

                // Mise à jour du solde de la caisse
                $compteCaisseADebiter->setSoldeRestant($compteCaisseADebiter->getSoldeRestant() - $montantADonner);
                $entityManager->persist($compteCaisseADebiter);

                $mouvementCompteClient->setTypeMouvement('DEBIT_ESPECES'); // Type de mouvement spécifique

                $entityManager->persist($mouvementCompteClient);

            } elseif ($typeRetrait === 'TRANSFERT') {
                $operation->setTypeOperation('RETRAIT_TRANSFERT_EN_ATTENTE');
                $operation->setSens("SORTIE"); // Conceptuellement, ça sort du système à terme

                // 4. Traitement des détails du bénéficiaire (si transfert)
                $nomBeneficiaire = $data['nomBeneficiaire'] ?? null;
                $contactBeneficiaire = $data['contactBeneficiaire'] ?? null;
                $typeCompteBeneficiaire = $data['typeCompteBeneficiaire'] ?? null;
                $detailCompte = $data['detailCompte'] ?? null;
                $institutionFinanciere = $data['institutionFinanciere'] ?? null;
                $paysBeneficiaireId = $data['paysBeneficiaire'] ?? null;
                $motifRetrait = $data['motifRetrait'] ?? null;

                // Validation minimale des champs de transfert
                if (!$nomBeneficiaire || !$typeCompteBeneficiaire || !$detailCompte || !$institutionFinanciere || !$paysBeneficiaireId) {
                    throw new \Exception('Veuillez fournir tous les détails du bénéficiaire pour le transfert.');
                }
                $paysBeneficiaire = $entityManager->getRepository(Pays::class)->find($paysBeneficiaireId);
                if (!$paysBeneficiaire) {
                    throw new \Exception('Pays du bénéficiaire invalide.');
                }


                // Créer ou récupérer le bénéficiaire
                $beneficiaire = $entityManager->getRepository(Beneficiaire::class)->findOneBy([
                    'profilClient' => $profilClient,
                    'nomComplet' => $nomBeneficiaire,
                    'typeCompte' => $typeCompteBeneficiaire,
                    'detailCompte' => $detailCompte,
                ]);

                if (!$beneficiaire) {
                    $beneficiaire = new Beneficiaire();
                    $beneficiaire->setProfilClient($profilClient);
                    $beneficiaire->setNomComplet($nomBeneficiaire);
                    $beneficiaire->setContact($contactBeneficiaire);
                    $beneficiaire->setTypeCompte($typeCompteBeneficiaire);
                    $beneficiaire->setDetailCompte($detailCompte);
                    $beneficiaire->setInstitutionFinanciere($institutionFinanciere);
                    $beneficiaire->setPays($paysBeneficiaire);
                    $beneficiaire->setCreatedAt(new DateTimeImmutable());
                    $entityManager->persist($beneficiaire);
                } else {
                    // Mettre à jour les informations si un bénéficiaire existant est utilisé et des champs sont modifiés
                    $beneficiaire->setContact($contactBeneficiaire);
                    $beneficiaire->setInstitutionFinanciere($institutionFinanciere);
                    $beneficiaire->setPays($paysBeneficiaire);
                    // ... mettez à jour d'autres champs si nécessaire
                }
                $operation->setBeneficiaire($beneficiaire); // Lier l'opération au bénéficiaire

                $operation->setMotif($motifRetrait); // Enregistrer le motif du retrait
                $mouvementCompteClient->setTypeMouvement('DEBIT_TRANSFERT_EN_ATTENTE'); // Type spécifique

                $entityManager->persist($mouvementCompteClient);

                // Pas de mouvement de caisse ENTRÉE/SORTIE immédiat pour les transferts, car ils sont "en attente"
                // et la sortie de cash sera gérée par un processus de validation ultérieur.


            } else {
                throw new \Exception('Type de retrait invalide.');
            }

            $entityManager->persist($operation); // Persiste l'opération principale


            $entityManager->flush();
            $entityManager->commit(); // Valide la transaction

            $this->addFlash('success', 'Retrait enregistré avec succès !');

            // --- Génération PDF et QR Code ---
            $qrCodeData = "Operation Ref: " . $operation->getId() .
                " | Type: " . $operation->getTypeOperation() .
                " | Client: " . $profilClient->getClient()->getNomComplet() .
                " | Montant Retiré: " . $operation->getMontantSource() . " " . $operation->getDeviseSource()->getCodeIso();

            if ($operation->getBeneficiaire()) {
                $qrCodeData .= " | Bénéficiaire: " . $operation->getBeneficiaire()->getNomComplet();
            }

            $qrCodeBase64 = $qrCodeService->generateQrCodeBase64($qrCodeData);

            $filename = sprintf('recu_retrait_%s_%s.pdf', $profilClient->getClient()->getNom(), $operation->getId());



            // Rendre le template Twig pour le PDF avec les données nécessaires
            $pdfPath = $pdfService->savePdf('operation/recu.html.twig', [
                'operation' => $operation,
                'qr_code' => $qrCodeBase64,
            ], $filename, 227,350);
            // Rediriger vers la nouvelle route d'impression avec le chemin du PDF
            return $this->redirectToRoute('app_operation_print', ['pdfPath' => $pdfPath]);

        } catch (\Exception $e) {
            $entityManager->rollback(); // Annule toutes les opérations si une erreur survient
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement du retrait : ' . $e->getMessage());
            return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
        }
    }


    #[Route('/transfert/finaliser-retrait/{id}', name: 'transfert_finaliser_retrait', methods: ['POST', 'GET'])]
    public function finaliserRetraitTransfert(
        Request $request,
        Operation $operation, // Symfony va injecter l'opération par son ID
        EntityManagerInterface $entityManager,
        CompteBancaireService $compteBancaireService // Injectez votre service CompteBancaireService
    ): Response {
        // Optionnel: Vérification du jeton CSRF si la route est POST et soumise via un formulaire
        // if ($request->isMethod('POST') && !$this->isCsrfTokenValid('finaliser_retrait_transfert_token', $request->request->get('_token'))) {
        //     $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez réessayer.');
        //     return $this->redirectToRoute('app_dashboard');
        // }

        // 1. Vérifier que l'opération est bien un retrait/transfert en attente
        if ($operation->getTypeOperation() !== 'RETRAIT_TRANSFERT_EN_ATTENTE') {
            $this->addFlash('danger', 'Cette opération n\'est pas un transfert de retrait en attente.');
            return $this->redirectToRoute('app_dashboard'); // Rediriger vers un tableau de bord ou une liste d'opérations
        }

        // 2. Vérifier que le transfert n'a pas déjà été finalisé
        // (Assurez-vous que votre entité Operation a un champ 'status' comme suggéré précédemment)
        if ($operation->isFinalise()) {
            $this->addFlash('warning', 'Ce transfert de retrait a déjà été finalisé.');
            return $this->redirectToRoute('app_dashboard');
        }

        // 3. Déterminer le compte bancaire de l'agence d'où les fonds vont partir.
        // C'est crucial : comment déterminez-vous le CompteBancaire spécifique ?
        // Options (comme discuté pour les dépôts bancaires):
        // a) L'opération de retrait initiale pourrait avoir une relation vers un CompteBancaire prédéfini ou sélectionné. (Le plus propre)
        //    Ex: $operation->getCompteBancaireAgenceSortie();
        // b) L'utilisateur qui finalise le transfert sélectionne le compte bancaire via un formulaire (si GET, on affiche le formulaire; si POST, on traite).
        // c) Logique métier pour trouver le compte par défaut (ex: par devise cible de l'opération).

        // Pour cet exemple, je vais simuler la sélection du compte bancaire via le request,
        // ou le trouver par devise cible si ce n'est pas envoyé par le formulaire.
        $compteBancaireId = $request->request->get('compteBancaireSortieId'); // Si soumis via un formulaire POST
        $compteBancaire = null;

        if ($compteBancaireId) {
            $compteBancaire = $entityManager->getRepository(CompteBancaire::class)->find($compteBancaireId);
        } else {
            // Logique de fallback ou par défaut si l'ID n'est pas explicitement fourni
            // Ex: Trouver un compte bancaire de l'agence avec la devise cible de l'opération
            $compteBancaire = $entityManager->getRepository(CompteBancaire::class)
                ->findOneBy(['devise' => $operation->getDeviseCible()]);
            // Ajoutez ici une logique plus robuste pour choisir le bon compte si vous en avez plusieurs par devise
        }

        if (!$compteBancaire) {
            $this->addFlash('danger', 'Impossible de déterminer le compte bancaire de l\'agence pour ce transfert.');
            return $this->redirectToRoute('app_dashboard');
        }

        // Vérifier que la devise du compte bancaire correspond à la devise cible du retrait
        if ($compteBancaire->getDevise()->getId() !== $operation->getDeviseCible()->getId()) {
            $this->addFlash('danger', sprintf(
                'La devise du compte bancaire sélectionné (%s) ne correspond pas à la devise de transfert (%s).',
                $compteBancaire->getDevise()->getCodeIso(),
                $operation->getDeviseCible()->getCodeIso()
            ));
            return $this->redirectToRoute('app_dashboard');
        }

        // Valider que le compte bancaire a un solde suffisant AVANT de débiter
        if ($compteBancaire->getSolde() < $operation->getMontantCible()) {
            $this->addFlash('danger', sprintf(
                'Solde insuffisant sur le compte bancaire %s (%s %s) pour finaliser le transfert de %s %s.',
                $compteBancaire->getNumeroCompte(),
                $compteBancaire->getSolde(),
                $compteBancaire->getDevise()->getCodeIso(),
                $operation->getMontantCible(),
                $operation->getDeviseCible()->getCodeIso()
            ));
            return $this->redirectToRoute('app_dashboard');
        }

        try {
            $entityManager->beginTransaction();

            // 4. Débiter le compte bancaire de l'agence via le service
            // Le montant à débiter est le montant cible de l'opération (le montant final à envoyer au bénéficiaire)
            $compteBancaireService->debiter($compteBancaire, $operation->getMontantCible());

            // 5. Enregistrer le mouvement sur le compte bancaire de l'agence
            $mouvementBancaire = new MouvementCompteBancaire();
            $mouvementBancaire->setCompteBancaire($compteBancaire);
            $mouvementBancaire->setTypeMouvement('RETRAIT_TRANSFERT'); // Type spécifique de mouvement bancaire
            $mouvementBancaire->setMontant($operation->getMontantCible());
            $mouvementBancaire->setDateMouvement(new DateTimeImmutable());
            $mouvementBancaire->setDateValeur(new DateTimeImmutable()); // Date de valeur réelle du virement
            $mouvementBancaire->setReferenceBancaire('TRF-OUT-' . $operation->getId()); // Générer une référence
            $entityManager->persist($mouvementBancaire);

            // 6. Mettre à jour le statut et le type de l'opération
            $operation->setTypeOperation('RETRAIT_TRANSFERT'); // Nouveau type d'opération
            $operation->setDateFinalisation(new DateTimeImmutable()); // Enregistrer la date de finalisation
            $operation->setCompteBancaire($compteBancaire); // Lier le compte bancaire d'où l'argent est sorti

            $entityManager->persist($operation); // Persiste les changements sur l'opération

            $entityManager->flush();
            $entityManager->commit();

            $this->addFlash('success', 'Transfert de retrait finalisé avec succès ! L\'argent a été débité du compte bancaire de l\'agence.');
            // Optionnel : Rediriger vers la vue de l'opération finalisée ou une liste des transferts
            return $this->redirectToRoute('app_dashboard');

        } catch (\Exception $e) {
            $entityManager->rollback();
            $this->addFlash('danger', 'Une erreur est survenue lors de la finalisation du transfert de retrait : ' . $e->getMessage());
            return $this->redirectToRoute('app_dashboard');
        }
    }
    #[Route('/{id}', name: 'app_operation_show', methods: ['GET'])]
    public function show(Operation $operation): Response
    {
        return $this->render('operation/show.html.twig', [
            'operation' => $operation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_operation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OperationForm::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('operation/edit.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_operation_delete', methods: ['POST'])]
    public function delete(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$operation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($operation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
    }
}
