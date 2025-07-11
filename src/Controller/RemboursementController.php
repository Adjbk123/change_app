<?php

namespace App\Controller;

use App\Entity\Remboursement;
use App\Form\RemboursementForm;
use App\Repository\RemboursementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\CaisseService;
use App\Repository\PretRepository;

#[Route('/remboursement')]
final class RemboursementController extends AbstractController
{
    #[Route(name: 'app_remboursement_index', methods: ['GET'])]
    public function index(RemboursementRepository $remboursementRepository): Response
    {
        return $this->render('remboursement/index.html.twig', [
            'remboursements' => $remboursementRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_remboursement_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CaisseService $caisseService, PretRepository $pretRepository): Response
    {
        $remboursement = new Remboursement();
        $form = $this->createForm(RemboursementForm::class, $remboursement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $pret = $remboursement->getPret();
            $devisePret = $pret->getDevise();
            $montantRembourse = (float) $remboursement->getMontantRembourse();

            // Récupérer la caisse affectée au caissier connecté
            $caisse = $caisseService->getCaisseAffectee($user);
            if (!$caisse) {
                $this->addFlash('danger', 'Aucune caisse affectée à votre profil.');
                return $this->render('remboursement/new.html.twig', [
                    'remboursement' => $remboursement,
                    'form' => $form,
                ]);
            }

            // Récupérer le compte caisse pour la devise
            $compteCaisse = $caisseService->getCompteCaisse($user, $devisePret);
            if (!$compteCaisse) {
                $this->addFlash('danger', 'Aucun compte caisse trouvé pour la devise du prêt.');
                return $this->render('remboursement/new.html.twig', [
                    'remboursement' => $remboursement,
                    'form' => $form,
                ]);
            }

            // Créditer le compte caisse
            $compteCaisse->setSoldeRestant((float)$compteCaisse->getSoldeRestant() + $montantRembourse);

            // Créer le mouvement de caisse
            $mouvement = new \App\Entity\MouvementCaisse();
            $mouvement->setCompteCaisse($compteCaisse);
            $mouvement->setTypeMouvement('REMBOURSEMENT_PRET');
            $mouvement->setMontant($montantRembourse);
            $mouvement->setDevise($devisePret);
            $mouvement->setEffectuePar($user);
            $mouvement->setDateMouvement(new \DateTimeImmutable());

            // Mettre à jour le prêt
            $pret->setMontantTotalRembourse((float)$pret->getMontantTotalRembourse() + $montantRembourse);
            $pret->setMontantRestant((float)$pret->getMontantRestant() - $montantRembourse);
            if ((float)$pret->getMontantRestant() <= 0) {
                $pret->setStatut('rembourse');
                $pret->setMontantRestant(0);
            }

            $entityManager->persist($compteCaisse);
            $entityManager->persist($mouvement);
            $entityManager->persist($pret);
            $entityManager->persist($remboursement);
            $entityManager->flush();

            $this->addFlash('success', 'Remboursement enregistré et mouvement de caisse créé avec succès.');
            return $this->redirectToRoute('app_remboursement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('remboursement/new.html.twig', [
            'remboursement' => $remboursement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_remboursement_show', methods: ['GET'])]
    public function show(Remboursement $remboursement): Response
    {
        return $this->render('remboursement/show.html.twig', [
            'remboursement' => $remboursement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_remboursement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Remboursement $remboursement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(RemboursementForm::class, $remboursement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_remboursement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('remboursement/edit.html.twig', [
            'remboursement' => $remboursement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_remboursement_delete', methods: ['POST'])]
    public function delete(Request $request, Remboursement $remboursement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$remboursement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($remboursement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_remboursement_index', [], Response::HTTP_SEE_OTHER);
    }
}
