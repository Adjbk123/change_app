<?php

namespace App\Controller;

use App\Entity\Agence;
use App\Entity\Caisse;
use App\Entity\CompteAgence;
use App\Form\AgenceForm;
use App\Form\CaisseForm;
use App\Form\CompteAgenceForm;
use App\Repository\AgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/agence')]
final class AgenceController extends AbstractController
{
    #[Route(name: 'app_agence_index', methods: ['GET'])]
    public function index(AgenceRepository $agenceRepository): Response
    {
        return $this->render('agence/index.html.twig', [
            'agences' => $agenceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_agence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $agence = new Agence();
        $form = $this->createForm(AgenceForm::class, $agence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($agence);
            $entityManager->flush();

            return $this->redirectToRoute('app_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('agence/new.html.twig', [
            'agence' => $agence,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_agence_show', methods: ['GET', 'POST'])]
    public function show(Agence $agence, Request $request, EntityManagerInterface $entityManager): Response
    {
        // --- GESTION DU FORMULAIRE CAISSE ---
        $caisse = new Caisse();
        $caisse->setAgence($agence);
        $caisse->setIsActive(true); // Supposons qu'une nouvelle caisse est active par défaut

        // Utilisez CaisseType si vous l'avez créé, sinon CaisseForm comme dans votre exemple initial
        $formCaisse = $this->createForm(CaisseForm::class, $caisse);
        $formCaisse->handleRequest($request);

        if ($formCaisse->isSubmitted() && $formCaisse->isValid()) {
            $entityManager->persist($caisse);
            $entityManager->flush();

            $this->addFlash('success', 'La caisse a été ajoutée avec succès à l\'agence ' . $agence->getNom() . ' !');

            // Rediriger pour rafraîchir la page et fermer le modal
            return $this->redirectToRoute('app_agence_show', ['id' => $agence->getId(), '_fragment' => 'caisses'], Response::HTTP_SEE_OTHER);
        }

        // --- GESTION DU FORMULAIRE COMPTE AGENCE ---
        $compteAgence = new CompteAgence();
        $compteAgence->setAgence($agence); // Assurez-vous que votre entité CompteAgence a une relation ManyToOne avec Agence

        $formCompteAgence = $this->createForm(CompteAgenceForm::class, $compteAgence); // Créez ce formulaire
        $formCompteAgence->handleRequest($request);

        if ($formCompteAgence->isSubmitted() && $formCompteAgence->isValid()) {
            $compteAgence->setSoldeInitial(0);
            $compteAgence->setSoldeRestant(0);

            $entityManager->persist($compteAgence);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte d\'agence a été ajouté avec succès à l\'agence ' . $agence->getNom() . ' !');

            // Rediriger pour rafraîchir la page et fermer le modal
            return $this->redirectToRoute('app_agence_show', ['id' => $agence->getId(), '_fragment' => 'comptes-agence'], Response::HTTP_SEE_OTHER);
        }

        // --- Rendu de la vue ---
        return $this->render('agence/show.html.twig', [
            'agence' => $agence,
            'formCaisse' => $formCaisse->createView(),
            'formCompteAgence' => $formCompteAgence->createView(), // Passer le nouveau formulaire
        ]);
    }

    #[Route('/{id}/edit', name: 'app_agence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Agence $agence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AgenceForm::class, $agence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('agence/edit.html.twig', [
            'agence' => $agence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_agence_delete', methods: ['POST'])]
    public function delete(Request $request, Agence $agence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$agence->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($agence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_agence_index', [], Response::HTTP_SEE_OTHER);
    }
}
