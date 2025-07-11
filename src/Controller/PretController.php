<?php

namespace App\Controller;

use App\Entity\Pret;
use App\Form\PretForm;
use App\Repository\PretRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CaisseService;
use App\Entity\Remboursement;
use App\Entity\MouvementCaisse;
use App\Entity\MouvementCompteClient;
use App\Service\CompteClientService;
use App\Repository\CompteClientRepository;

#[Route('/pret')]
final class PretController extends AbstractController
{
    #[Route(name: 'app_pret_index', methods: ['GET'])]
    public function index(PretRepository $pretRepository): Response
    {
        $user = $this->getUser();
        $agence = method_exists($user, 'getAgence') ? $user->getAgence() : null;
        $prets = [];
        if ($agence) {
            $prets = $pretRepository->findBy(['agence' => $agence]);
        }
        return $this->render('pret/index.html.twig', [
            'prets' => $prets,
        ]);
    }

    #[Route('/new', name: 'app_pret_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CaisseService $caisseService): Response
    {
        $pret = new Pret();
        $form = $this->createForm(PretForm::class, $pret);
        $form->remove('agence');
        $form->remove('caisse');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $devisePret = $pret->getDevise();
            $montantPret = (float) $pret->getMontantPrincipal();

            // Récupérer la caisse affectée au caissier connecté
            $caisse = $caisseService->getCaisseAffectee($user);
            if (!$caisse) {
                $this->addFlash('danger', 'Aucune caisse affectée à votre profil.');
                return $this->render('pret/new.html.twig', [
                    'pret' => $pret,
                    'form' => $form,
                ]);
            }

            // Récupérer le compte caisse pour la devise
            $compteCaisse = $caisseService->getCompteCaisse($user, $devisePret);
            if (!$compteCaisse) {
                $this->addFlash('danger', 'Aucun compte caisse trouvé pour la devise du prêt.');
                return $this->render('pret/new.html.twig', [
                    'pret' => $pret,
                    'form' => $form,
                ]);
            }

            // Vérifier le solde suffisant
            if ((float)$compteCaisse->getSoldeRestant() < $montantPret) {
                $this->addFlash('danger', 'Solde insuffisant dans la caisse pour accorder ce prêt.');
                return $this->render('pret/new.html.twig', [
                    'pret' => $pret,
                    'form' => $form,
                ]);
            }

            // Lier automatiquement la caisse et l'agence au prêt
            $pret->setCaisse($caisse);
            if (method_exists($user, 'getAgence')) {
                $pret->setAgence($user->getAgence());
            }

            // Débiter le compte caisse
            $compteCaisse->setSoldeRestant((float)$compteCaisse->getSoldeRestant() - $montantPret);

            // Créer le mouvement de caisse
            $mouvement = new \App\Entity\MouvementCaisse();
            $mouvement->setCompteCaisse($compteCaisse);
            $mouvement->setTypeMouvement('DECAISSEMENT_PRET');
            $mouvement->setMontant($montantPret);
            $mouvement->setDevise($devisePret);
            $mouvement->setEffectuePar($user);
            $mouvement->setDateMouvement(new \DateTimeImmutable());

            $entityManager->persist($compteCaisse);
            $entityManager->persist($mouvement);
            $entityManager->persist($pret);
            $entityManager->flush();

            $this->addFlash('success', 'Prêt accordé et mouvement enregistré avec succès.');
            return $this->redirectToRoute('app_pret_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pret/new.html.twig', [
            'pret' => $pret,
            'form' => $form,
        ]);
    }

    #[Route('/auto-remboursement', name: 'app_pret_auto_remboursement', methods: ['GET'])]
    public function autoRemboursement(
        PretRepository $pretRepository,
        CompteClientService $compteClientService,
        EntityManagerInterface $entityManager,
        CaisseService $caisseService
    ): Response {
        $now = new \DateTime();
        $prets = $pretRepository->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->andWhere('p.montantRestant > 0')
            ->andWhere('p.dureeMois IS NOT NULL')
            ->setParameter('statut', 'en cours')
            ->getQuery()->getResult();

        $nbSucces = 0;
        $nbEchecs = 0;
        foreach ($prets as $pret) {
            // Calcul de la date d'échéance
            $dateOctroi = $pret->getCreatedAt() ?? null;
            $dureeMois = $pret->getDureeMois();
            if (!$dateOctroi || !$dureeMois) continue;
            $dateEcheance = (clone $dateOctroi)->modify('+' . $dureeMois . ' months');
            if ($now < $dateEcheance) continue; // pas encore à échéance

            $profilClient = $pret->getProfilClient();
            $devise = $pret->getDevise();
            $montantRestant = (float)$pret->getMontantRestant();
            $compteClient = $compteClientService->getOrCreateCompteClient($profilClient, $devise);

            if ((float)$compteClient->getSoldeActuel() >= $montantRestant) {
                // Débiter le compte client
                $compteClient->retirer($montantRestant);
                $entityManager->persist($compteClient);

                // Mouvement compte client
                $mvtClient = new MouvementCompteClient();
                $mvtClient->setCompteClient($compteClient);
                $mvtClient->setTypeMouvement('RETRAIT_REMBOURSEMENT_PRET_AUTO');
                $mvtClient->setSens('DEBIT');
                $mvtClient->setMontant($montantRestant);
                $mvtClient->setDateMouvement(new \DateTime());
                $entityManager->persist($mvtClient);

                // Créditer la caisse de l'agent du prêt
                $user = $pret->getAgentOctroi();
                $caisse = $caisseService->getCaisseAffectee($user);
                $compteCaisse = $caisseService->getCompteCaisse($user, $devise);
                if ($compteCaisse) {
                    $compteCaisse->setSoldeRestant((float)$compteCaisse->getSoldeRestant() + $montantRestant);
                    $entityManager->persist($compteCaisse);

                    // Mouvement caisse
                    $mvtCaisse = new MouvementCaisse();
                    $mvtCaisse->setCompteCaisse($compteCaisse);
                    $mvtCaisse->setTypeMouvement('REMBOURSEMENT_PRET_AUTO');
                    $mvtCaisse->setMontant($montantRestant);
                    $mvtCaisse->setDevise($devise);
                    $mvtCaisse->setEffectuePar($user);
                    $mvtCaisse->setDateMouvement(new \DateTimeImmutable());
                    $entityManager->persist($mvtCaisse);
                }

                // Enregistrer le remboursement
                $remboursement = new Remboursement();
                $remboursement->setPret($pret);
                $remboursement->setMontantRembourse($montantRestant);
                $remboursement->setDateRemboursement(new \DateTime());
                $remboursement->setAgent($user);
                $remboursement->setTypePaiement('AUTOMATIQUE');
                $entityManager->persist($remboursement);

                // Mettre à jour le prêt
                $pret->setMontantTotalRembourse((float)$pret->getMontantTotalRembourse() + $montantRestant);
                $pret->setMontantRestant(0);
                $pret->setStatut('rembourse');
                $entityManager->persist($pret);
                $nbSucces++;
            } else {
                // Solde insuffisant, marquer en retard
                $pret->setStatut('en retard');
                $entityManager->persist($pret);
                $nbEchecs++;
            }
        }
        $entityManager->flush();
        return new Response("Auto-remboursement terminé : $nbSucces succès, $nbEchecs échecs.");
    }

    #[Route('/{id}', name: 'app_pret_show', methods: ['GET'])]
    public function show(Pret $pret): Response
    {
        return $this->render('pret/show.html.twig', [
            'pret' => $pret,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_pret_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pret $pret, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PretForm::class, $pret);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_pret_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pret/edit.html.twig', [
            'pret' => $pret,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pret_delete', methods: ['POST'])]
    public function delete(Request $request, Pret $pret, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pret->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pret);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_pret_index', [], Response::HTTP_SEE_OTHER);
    }
}
