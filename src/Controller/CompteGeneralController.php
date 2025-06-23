<?php

namespace App\Controller;

use App\Entity\ApproCompteGeneral;
use App\Entity\CompteGeneral;
use App\Form\ApproCompteGeneralForm;
use App\Form\CompteGeneralForm;
use App\Repository\CompteGeneralRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compte-general')]
final class CompteGeneralController extends AbstractController
{
    #[Route('/', name: 'app_compte_general_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        CompteGeneralRepository $compteGeneralRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $compteGeneral = new CompteGeneral();
        $form = $this->createForm(CompteGeneralForm::class, $compteGeneral);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $devise = $compteGeneral->getDevise();

            // Vérifier si un compte avec cette devise existe déjà
            $existant = $compteGeneralRepository->findOneBy(['devise' => $devise]);

            if ($existant) {
                $this->addFlash('warning', 'Un compte principal existe déjà pour la devise "' . $devise->getNom() . '".');
            } else {
                $compteGeneral->setSoldeInitial(0);
                $compteGeneral->setSoldeRestant(0);
                $entityManager->persist($compteGeneral);
                $entityManager->flush();

                $this->addFlash('success', 'Le compte principal a été ajouté avec succès !');

                return $this->redirectToRoute('app_compte_general_index', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('compte_general/index.html.twig', [
            'compte_generals' => $compteGeneralRepository->findAll(),
            'formCompteGeneral' => $form->createView(),
        ]);
    }


    #[Route('/new', name: 'app_compte_general_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $compteGeneral = new CompteGeneral();
        $form = $this->createForm(CompteGeneralForm::class, $compteGeneral);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($compteGeneral);
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_general_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_general/new.html.twig', [
            'compte_general' => $compteGeneral,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_general_show', methods: ['GET', 'POST'])] // Ajout de 'POST' pour gérer la soumission du formulaire
    public function show(CompteGeneral $compteGeneral, Request $request, EntityManagerInterface $entityManager): Response
    {
        // --- GESTION DU FORMULAIRE D'APPROVISIONNEMENT ---
        $approCompteGeneral = new ApproCompteGeneral();
        $approCompteGeneral->setCompteGeneral($compteGeneral);
        $approCompteGeneral->setDevise($compteGeneral->getDevise());
        $approCompteGeneral->setDateAppro(new \DateTime());

        // Utilisez ApproCompteGeneralType si vous l'avez créé, sinon ApproCompteGeneralForm
        $formApproCompteGeneral = $this->createForm(ApproCompteGeneralForm::class, $approCompteGeneral);
        $formApproCompteGeneral->handleRequest($request);

        if ($formApproCompteGeneral->isSubmitted() && $formApproCompteGeneral->isValid()) {
            $montant = $approCompteGeneral->getMontant();

            // Mise à jour du solde initial ET du solde restant
            $compteGeneral->setSoldeInitial($compteGeneral->getSoldeInitial() + $montant);
            $compteGeneral->setSoldeRestant($compteGeneral->getSoldeRestant() + $montant);

            $approCompteGeneral->setCompteGeneral($compteGeneral);
            $approCompteGeneral->setApprovisionnePar($this->getUser());

            $entityManager->persist($approCompteGeneral);
            $entityManager->flush();

            $this->addFlash('success', 'L\'approvisionnement a été ajouté avec succès ! Le solde initial et le solde restant ont été mis à jour.');

            return $this->redirectToRoute('app_compte_general_show', [
                'id' => $compteGeneral->getId(),
            ], Response::HTTP_SEE_OTHER);
        }


        // --- Rendu de la vue ---
        return $this->render('compte_general/show.html.twig', [
            'compte_general' => $compteGeneral,
            'formApproCompteGeneral' => $formApproCompteGeneral->createView(), // Passer le formulaire au template
        ]);
    }

    #[Route('/{id}/edit', name: 'app_compte_general_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CompteGeneral $compteGeneral, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompteGeneralForm::class, $compteGeneral);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_general_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_general/edit.html.twig', [
            'compte_general' => $compteGeneral,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_general_delete', methods: ['POST'])]
    public function delete(Request $request, CompteGeneral $compteGeneral, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$compteGeneral->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($compteGeneral);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_compte_general_index', [], Response::HTTP_SEE_OTHER);
    }
}
