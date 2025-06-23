<?php

namespace App\Controller;

use App\Entity\ApproCompteBancaire;
use App\Entity\Banque;
use App\Entity\CompteBancaire;
use App\Entity\MouvementCompteBancaire;
use App\Entity\Pays;
use App\Form\ApproCompteBancaireForm;
use App\Form\CompteBancaireForm;
use App\Repository\BanqueRepository;
use App\Repository\CompteBancaireRepository;
use App\Repository\PaysRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/compte-bancaire')]
final class CompteBancaireController extends AbstractController
{
    #[Route(name: 'app_compte_bancaire_index', methods: ['GET'])]
    public function index(CompteBancaireRepository $compteBancaireRepository): Response
    {
        return $this->render('compte_bancaire/index.html.twig', [
            'compte_bancaires' => $compteBancaireRepository->findAll(),
        ]);
    }




    #[Route('/pays', name: 'app_pays_compte_bancaire_index', methods: ['GET'])]
    public function indexCompteBancaire(EntityManagerInterface $em, PaysRepository $paysRepository, CompteBancaireRepository $compteBancaireRepository): Response
    {
        $pays = $paysRepository->findAll();

        // Pour chaque pays, compter le nombre de comptes bancaires
        $paysData = [];
        foreach ($pays as $p) {
            $nombreComptes = $compteBancaireRepository// Assurez-vous d'importer CompteBancaire
            ->count(['pays' => $p]); // Compte les comptes liés à ce pays
            $paysData[] = [
                'pays' => $p,
                'nombre_comptes' => $nombreComptes,
            ];
        }

        return $this->render('pays/indexCompteBancaire.html.twig', [
            'pays_data' => $paysData,
        ]);
    }

    #[Route('/par-pays/{id}', name: 'app_banque_by_pays', methods: ['GET'])]
    public function showByPays(
        Pays $pays,
        CompteBancaireRepository $compteBancaireRepository
    ): Response {
        // Récupère tous les comptes bancaires liés à ce pays
        $comptes = $compteBancaireRepository->findBy(['pays' => $pays]);

        // Regroupe les comptes par banque
        $banques = [];

        foreach ($comptes as $compte) {
            $banque = $compte->getBanque();
            $banqueId = $banque->getId();

            if (!isset($banques[$banqueId])) {
                $banques[$banqueId] = [
                    'banque' => $banque,
                    'comptes' => [],
                ];
            }

            $banques[$banqueId]['comptes'][] = $compte;
        }

        return $this->render('banque/by_pays.html.twig', [
            'pays' => $pays,
            'banques' => $banques,
        ]);
    }

    #[Route('/compte-bancaire/approvisionner/{id}', name: 'app_compte_bancaire_appro', methods: ['POST'])]
    public function approvisionnerCompteBancaire(
        Request $request,
        CompteBancaire $compteBancaire,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $montant = (float) $request->request->get('montant');
        $motif = $request->request->get('motif');
        $token = new CsrfToken('approvisionnement_bancaire' . $compteBancaire->getId(), $request->request->get('_token'));

        // Sécurité
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide pour l\'approvisionnement.');
            return $this->redirectToRoute('app_banque_by_pays', ['id' => $compteBancaire->getPays()->getId()]);
        }

        if ($montant <= 0) {
            $this->addFlash('error', 'Le montant doit être positif.');
            return $this->redirectToRoute('app_banque_by_pays', ['id' => $compteBancaire->getPays()->getId()]);
        }

        // Mise à jour du solde
        $compteBancaire->setSoldeInitial($compteBancaire->getSoldeInitial() + $montant);
        $compteBancaire->setSoldeRestant($compteBancaire->getSoldeRestant() + $montant);

        // Enregistrement du Mouvement
        $mouvement = new MouvementCompteBancaire();
        $mouvement->setCompteBancaire($compteBancaire);
        $mouvement->setTypeMouvement('approvisionnement');
        $mouvement->setMontant($montant);
        $mouvement->setSens('credit');
        $mouvement->setDevise($compteBancaire->getDevise());
        $mouvement->setEffectuePar($this->getUser());
        $mouvement->setDateMouvement(new \DateTime());
        $em->persist($mouvement);

        // Enregistrement de l'Approvisionnement
        $appro = new ApproCompteBancaire();
        $appro->setCompteBancaire($compteBancaire);
        $appro->setDevise($compteBancaire->getDevise());
        $appro->setMontant($montant);
        $appro->setDateAppro(new \DateTime());
        $em->persist($appro);

        // Flush global
        $em->flush();

        $this->addFlash('success', sprintf(
            'Le compte bancaire %s a été approvisionné de %s %s.',
            $compteBancaire->getNumeroBancaire(),
            number_format($montant, 0, ',', ' '),
            $compteBancaire->getDevise()->getNom()
        ));

        return $this->redirectToRoute('app_banque_by_pays', ['id' => $compteBancaire->getPays()->getId()]);
    }

    #[Route('/new', name: 'app_compte_bancaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $compteBancaire = new CompteBancaire();
        $form = $this->createForm(CompteBancaireForm::class, $compteBancaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($compteBancaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_bancaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_bancaire/new.html.twig', [
            'compte_bancaire' => $compteBancaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_bancaire_show', methods: ['GET'])]
    public function show(CompteBancaire $compteBancaire): Response
    {
        return $this->render('compte_bancaire/show.html.twig', [
            'compte_bancaire' => $compteBancaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_compte_bancaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CompteBancaire $compteBancaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompteBancaireForm::class, $compteBancaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_bancaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_bancaire/edit.html.twig', [
            'compte_bancaire' => $compteBancaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_bancaire_delete', methods: ['POST'])]
    public function delete(Request $request, CompteBancaire $compteBancaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$compteBancaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($compteBancaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_compte_bancaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
