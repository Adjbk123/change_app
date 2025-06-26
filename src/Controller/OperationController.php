<?php

namespace App\Controller;

use App\Entity\Beneficiaire;
use App\Entity\Client;
use App\Entity\Devise;
use App\Entity\MouvementCaisse;
use App\Entity\MouvementCompteClient;
use App\Entity\Operation;
use App\Entity\Pays;
use App\Entity\PieceIdentite;
use App\Entity\ProfilClient;
use App\Entity\TypeClient;
use App\Form\OperationForm;
use App\Repository\OperationRepository;
use App\Service\CaisseService;
use App\Service\CompteClientService;
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


      #[Route('/new/{type}', name: 'app_operation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        string $type,
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
            'type'=>$type,
            'pays'=>$pays
        ]);
    }

    #[Route('/achat-vente/store', name: 'operation_achat_vente_new', methods: ['POST'])]
    public function storeAchatVente(
        Request $request,
        EntityManagerInterface $entityManager,
        CaisseService $caisseService
    ): Response {
        if (!$this->isCsrfTokenValid('new_operation_token', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_operation_new');
        }

        $data = $request->request->all();
        $user = $this->getUser();

        // Création de l'opération
        $operation = new Operation();
        $operation->setTypeOperation($data['typeOperation']);
        $operation->setCreatedAt(new \DateTimeImmutable());
        $operation->setAgent($user);

        // Récupération des entités liées
        $client = $entityManager->getRepository(Client::class)->find($data['client'] ?? null);
        $deviseSource = $entityManager->getRepository(Devise::class)->find($data['deviseSource'] ?? null);
        $deviseCible = $entityManager->getRepository(Devise::class)->find($data['deviseCible'] ?? null);

        if ($client) $operation->setClient($client);
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

        // Déterminer la devise et le montant qui concerne le mouvement de caisse
        $devisePourCaisse = ($sens === "ENTREE") ? $deviseSource : $deviseCible;
        $montantPourCaisse = ($sens === "ENTREE") ? $montantSource : $montantCible;

        // Récupération du compte caisse
        $compteCaisse = $caisseService->getCompteCaisse($user, $devisePourCaisse);
        if (!$compteCaisse) {
            $this->addFlash('danger', "Aucun compte caisse disponible pour la devise " . $devisePourCaisse->getCodeIso());
            return $this->redirectToRoute('app_operation_new');
        }



        // Création du mouvement de caisse
        $mouvement = new MouvementCaisse();
        $mouvement->setCompteCaisse($compteCaisse);
        $mouvement->setTypeMouvement($sens);
        $mouvement->setMontant($montantPourCaisse);
        $mouvement->setDateMouvement(new \DateTimeImmutable());

        // ✅ Mise à jour du solde
        if ($sens === 'ENTREE') {
            $compteCaisse->setSoldeRestant($compteCaisse->getSoldeRestant() + $montantPourCaisse);
        } else {
            $compteCaisse->setSoldeRestant($compteCaisse->getSoldeRestant() - $montantPourCaisse);
        }
        // Enregistrement
        $entityManager->persist($operation);
        $entityManager->persist($mouvement);
        $entityManager->flush();

        $this->addFlash('success', 'Opération enregistrée avec succès !');
        return $this->redirectToRoute('app_operation_index');
    }

    #[Route('/versement/store', name: 'operation_versement_new', methods: ['POST'])]
    public function storeVersement(
        Request $request,
        EntityManagerInterface $entityManager,
        CaisseService $caisseService,
        CompteClientService $compteClientService
    ): Response {
        if (!$this->isCsrfTokenValid('new_operation_token', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Jeton de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
        }

        $data = $request->request->all();

        $user = $this->getUser();

        $profilClientId = $data['profilClient'] ?? null;
        $profilClient = $entityManager->getRepository(ProfilClient::class)->find($profilClientId);

        $deviseApportee = $entityManager->getRepository(Devise::class)->find($data['deviseApportee'] ?? null);
        $montantApporte = (float)($data['montantApporte'] ?? 0);
        $deviseCibleCompte = $entityManager->getRepository(Devise::class)->find($data['deviseCibleCompte'] ?? null);

        //dd($data,$deviseCibleCompte ,$montantApporte,$deviseApportee, $profilClient);

        // Cas 1 : Dépôt classique (sans conversion)
        if ($deviseApportee->getId() === $deviseCibleCompte->getId()) {
            $operation = new Operation();
            $operation->setTypeOperation('DEPOT');
            $operation->setCreatedAt(new \DateTimeImmutable());
            $operation->setAgent($user);
            $operation->setDeviseSource($deviseApportee);
            $operation->setMontantSource($montantApporte);
            $operation->setDeviseCible($deviseCibleCompte);
            $operation->setMontantCible($montantApporte);
            $operation->setTaux($data['tauxDepot'] );
            $operation->setCaisse($caisseService->getCaisseAffectee($user));
            $operation->setSens("ENTREE");

            // Récupère le CompteClient pour le dépôt
            $compteClient = $compteClientService->getOrCreateCompteClient($profilClient, $deviseApportee);
            $operation->setCompteClientCible($compteClient); // Lier l'opération au CompteClient cible

            $compteCaisse = $caisseService->getCompteCaisse($user, $deviseApportee);
            if (!$compteCaisse) {
                $this->addFlash('danger', "Aucun compte caisse disponible pour la devise " . $deviseApportee->getCodeIso());
                return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
            }

            // Mouvement de caisse (ENTREE)
            $mouvementCaisse = new MouvementCaisse(); // Renommé pour clarté
            $mouvementCaisse->setCompteCaisse($compteCaisse);
            $mouvementCaisse->setTypeMouvement("ENTREE");
            $mouvementCaisse->setMontant($montantApporte);
            $mouvementCaisse->setDateMouvement(new \DateTimeImmutable());
            $entityManager->persist($mouvementCaisse);

            $compteCaisse->setSoldeRestant($compteCaisse->getSoldeRestant() + $montantApporte);
            $entityManager->persist($compteCaisse);

            // Créditer le CompteClient
            $compteClientService->deposer($compteClient, $montantApporte);

            // NOUVEAU : Création du MouvementCompteClient
            $mouvementCompteClient = new MouvementCompteClient();
            $mouvementCompteClient->setCompteClient($compteClient); // Lié au CompteClient crédité
            $mouvementCompteClient->setTypeMouvement('DEPOT'); // Ou 'DEPOT' si plus spécifique
            $mouvementCompteClient->setSens('CREDIT'); // Ou 'DEPOT' si plus spécifique
            $mouvementCompteClient->setMontant($montantApporte);
            $mouvementCompteClient->setDateMouvement(new \DateTime());
            $entityManager->persist($mouvementCompteClient);


            $entityManager->persist($operation);
            $entityManager->flush();

            $this->addFlash('success', 'Dépôt classique enregistré avec succès !');
            return $this->redirectToRoute('app_operation_index');

        } else {
            // Cas 2 : Dépôt avec conversion (achat/vente préalable)
            $taux = (float)($data['tauxDepot'] ?? 0);
            $montantConverti = (float)($data['montantCibleCompte'] ?? 0);

            if ($taux <= 0 || $montantConverti <= 0) {
                $this->addFlash('danger', 'Erreur de calcul du taux ou du montant converti. Veuillez réessayer.');
                return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
            }

            $operationAchat = new Operation();
            $operationAchat->setTypeOperation('ACHAT'); // L'opération principale est un achat pour l'agence
            $operationAchat->setCreatedAt(new \DateTimeImmutable());
            $operationAchat->setAgent($user);
            $operationAchat->setDeviseSource($deviseApportee);
            $operationAchat->setMontantSource($montantApporte);
            $operationAchat->setDeviseCible($deviseCibleCompte);
            $operationAchat->setMontantCible($montantConverti);
            $operationAchat->setTaux($taux);
            $operationAchat->setCaisse($caisseService->getCaisseAffectee($user));
            $operationAchat->setSens("ENTREE"); // Argent apporté entre en caisse (DeviseSource)

            // Récupère le CompteClient pour le dépôt final
            $compteClient = $compteClientService->getOrCreateCompteClient($profilClient, $deviseCibleCompte);
            $operationAchat->setCompteClientCible($compteClient); // Lier l'opération au CompteClient crédité

            // Mouvement de caisse pour la devise apportée (ENTREE)
            $compteCaisseApportee = $caisseService->getCompteCaisse($user, $deviseApportee);
            if (!$compteCaisseApportee) {
                $this->addFlash('danger', "Aucun compte caisse disponible pour la devise apportée " . $deviseApportee->getCodeIso());
                return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
            }
            $mouvementApporteeCaisse = new MouvementCaisse(); // Renommé
            $mouvementApporteeCaisse->setCompteCaisse($compteCaisseApportee);
            $mouvementApporteeCaisse->setTypeMouvement("ENTREE");
            $mouvementApporteeCaisse->setMontant($montantApporte);
            $mouvementApporteeCaisse->setDateMouvement(new \DateTimeImmutable());
            $entityManager->persist($mouvementApporteeCaisse);

            $compteCaisseApportee->setSoldeRestant($compteCaisseApportee->getSoldeRestant() + $montantApporte);
            $entityManager->persist($compteCaisseApportee);

            // Mouvement de caisse pour la devise cible (SORTIE)
            $compteCaisseCible = $caisseService->getCompteCaisse($user, $deviseCibleCompte);
            if (!$compteCaisseCible) {
                $this->addFlash('danger', "Aucun compte caisse disponible pour la devise cible " . $deviseCibleCompte->getCodeIso());
                return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
            }
            if ($compteCaisseCible->getSoldeRestant() < $montantConverti) {
                $this->addFlash('danger', "Solde caisse insuffisant en " . $deviseCibleCompte->getCodeIso() . " pour la conversion.");
                return $this->redirectToRoute('app_operation_new', ['type' => 'depot']);
            }
            $mouvementCibleCaisse = new MouvementCaisse(); // Renommé
            $mouvementCibleCaisse->setCompteCaisse($compteCaisseCible);
            $mouvementCibleCaisse->setTypeMouvement("SORTIE");
            $mouvementCibleCaisse->setMontant($montantConverti);
            $mouvementCibleCaisse->setDateMouvement(new \DateTimeImmutable());
            $entityManager->persist($mouvementCibleCaisse);

            $compteCaisseCible->setSoldeRestant($compteCaisseCible->getSoldeRestant() - $montantConverti);
            $entityManager->persist($compteCaisseCible);

            $entityManager->persist($operationAchat);

            // Créditer le compte client avec le montant converti
            $compteClientService->deposer($compteClient, $montantConverti);

            // NOUVEAU : Création du MouvementCompteClient pour la conversion
            $mouvementCompteClient = new MouvementCompteClient();
            $mouvementCompteClient->setCompteClient($compteClient); // Lié au CompteClient crédité
            $mouvementCompteClient->setTypeMouvement('DEPOT'); // Ou un type spécifique 'DEPOT_CONVERSION'
            $mouvementCompteClient->setMontant($montantConverti);
            $mouvementCompteClient->setSens('CREDIT');
            $mouvementCompteClient->setDateMouvement(new \DateTime());
            $entityManager->persist($mouvementCompteClient);


            $entityManager->flush();

            $this->addFlash('success', 'Dépôt avec conversion enregistré avec succès !');
            return $this->redirectToRoute('app_operation_index');
        }
    }

    #[Route('/retrait/store', name: 'operation_retrait_new', methods: ['POST'])]
    public function storeRetrait(
        Request $request,
        EntityManagerInterface $entityManager,
        CaisseService $caisseService,
        CompteClientService $compteClientService,

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
            $operation->setCreatedAt(new \DateTimeImmutable());
            $operation->setAgent($user);
            $operation->setTypeOperation("RETRAIT");
            $operation->setClient($profilClient->getClient());
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
                $operation->setSens('DEBIT');

                // Mouvement de caisse (SORTIE)
                $compteCaisseADebiter = $caisseService->getCompteCaisse($user, $deviseADonner);
                if (!$compteCaisseADebiter) {
                    $this->addFlash('danger', "Aucun compte caisse disponible pour la devise " . $deviseADonner->getCodeIso());
                    $entityManager->rollback();
                    return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
                }
                // Vérification du solde de caisse (CRUCIAL pour espèces)
                if ($compteCaisseADebiter->getSoldeRestant() < $montantADonner) {
                    $this->addFlash('danger', "Solde caisse insuffisant en " . $deviseADonner->getCodeIso() . " pour ce retrait.");
                    $entityManager->rollback();
                    return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
                }

                $mouvementCaisse = new MouvementCaisse();
                $mouvementCaisse->setCompteCaisse($compteCaisseADebiter);
                $mouvementCaisse->setTypeMouvement("SORTIE");
                $mouvementCaisse->setMontant($montantADonner);
                $mouvementCaisse->setDateMouvement(new \DateTimeImmutable());
                $entityManager->persist($mouvementCaisse);

                // Mise à jour du solde de la caisse
                $compteCaisseADebiter->setSoldeRestant($compteCaisseADebiter->getSoldeRestant() - $montantADonner);
                $entityManager->persist($compteCaisseADebiter);

                $mouvementCompteClient->setTypeMouvement('DEBIT_ESPECES'); // Type de mouvement spécifique

                $entityManager->persist($mouvementCompteClient);
                //dd($compteClientADebiter, $compteCaisseADebiter, $operation);

            } elseif ($typeRetrait === 'TRANSFERT') {
                $operation->setTypeOperation('RETRAIT_TRANSFERT_EN_ATTENTE'); // Enregistre comme en attente
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
                    $this->addFlash('danger', 'Veuillez fournir tous les détails du bénéficiaire pour le transfert.');
                    $entityManager->rollback();
                    return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
                }
                $paysBeneficiaire = $entityManager->getRepository(Pays::class)->find($paysBeneficiaireId);
                if (!$paysBeneficiaire) {
                    $this->addFlash('danger', 'Pays du bénéficiaire invalide.');
                    $entityManager->rollback();
                    return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
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
                    $beneficiaire->setCreatedAt(new \DateTimeImmutable());
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
                $this->addFlash('danger', 'Type de retrait invalide.');
                $entityManager->rollback();
                return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
            }

            $entityManager->persist($operation); // Persiste l'opération principale


            $entityManager->flush();
            $entityManager->commit(); // Valide la transaction

            $this->addFlash('success', 'Retrait enregistré avec succès !');
            return $this->redirectToRoute('app_operation_index');

        } catch (\Exception $e) {
            $entityManager->rollback(); // Annule toutes les opérations si une erreur survient
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement du retrait : ' . $e->getMessage());
            return $this->redirectToRoute('app_operation_new', ['type' => 'retrait']);
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
