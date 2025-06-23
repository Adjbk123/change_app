<?php

namespace App\Controller;

use App\Entity\Banque;
use App\Entity\CompteBancaire;
use App\Form\BanqueForm;
use App\Form\CompteBancaireForm;
use App\Repository\BanqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/banque')]
final class BanqueController extends AbstractController
{
    #[Route(name: 'app_banque_index', methods: ['GET'])]
    public function index(BanqueRepository $banqueRepository): Response
    {
        return $this->render('banque/index.html.twig', [
            'banques' => $banqueRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_banque_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $banque = new Banque();
        $form = $this->createForm(BanqueForm::class, $banque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($banque);
            $entityManager->flush();

            return $this->redirectToRoute('app_banque_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('banque/new.html.twig', [
            'banque' => $banque,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'app_banque_show', methods: ['GET', 'POST'])]
    public function show(Banque $banque, Request $request, EntityManagerInterface $entityManager): Response
    {
        // Créer une nouvelle instance de CompteBancaire
        $compteBancaire = new CompteBancaire();
        // Associer ce compte bancaire à la banque actuelle
        $compteBancaire->setBanque($banque);

        // Créer le formulaire pour le CompteBancaire
        $formCompteBancaire = $this->createForm(CompteBancaireForm::class, $compteBancaire);
        $formCompteBancaire->handleRequest($request);

        // Gérer la soumission du formulaire du modal
        if ($formCompteBancaire->isSubmitted() && $formCompteBancaire->isValid()) {
            $compteBancaire->setSoldeInitial(0);
            $compteBancaire->setSoldeRestant(0);

            $entityManager->persist($compteBancaire);
            $entityManager->flush();

            $this->addFlash('success', 'Le compte bancaire a été ajouté avec succès !');

            // Rediriger vers la même page pour afficher les modifications et fermer le modal
            return $this->redirectToRoute('app_banque_show', ['id' => $banque->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('banque/show.html.twig', [
            'banque' => $banque,
            'formCompteBancaire' => $formCompteBancaire->createView(), // Passer le formulaire au template
        ]);
    }
    #[Route('/{id}/edit', name: 'app_banque_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Banque $banque, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BanqueForm::class, $banque);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_banque_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('banque/edit.html.twig', [
            'banque' => $banque,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_banque_delete', methods: ['POST'])]
    public function delete(Request $request, Banque $banque, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$banque->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($banque);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_banque_index', [], Response::HTTP_SEE_OTHER);
    }
}
