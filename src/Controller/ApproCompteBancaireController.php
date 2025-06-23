<?php

namespace App\Controller;

use App\Entity\ApproCompteBancaire;
use App\Form\ApproCompteBancaireForm;
use App\Repository\ApproCompteBancaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/appro/compte/bancaire')]
final class ApproCompteBancaireController extends AbstractController
{
    #[Route(name: 'app_appro_compte_bancaire_index', methods: ['GET'])]
    public function index(ApproCompteBancaireRepository $approCompteBancaireRepository): Response
    {
        return $this->render('appro_compte_bancaire/index.html.twig', [
            'appro_compte_bancaires' => $approCompteBancaireRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_appro_compte_bancaire_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $approCompteBancaire = new ApproCompteBancaire();
        $form = $this->createForm(ApproCompteBancaireForm::class, $approCompteBancaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($approCompteBancaire);
            $entityManager->flush();

            return $this->redirectToRoute('app_appro_compte_bancaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_compte_bancaire/new.html.twig', [
            'appro_compte_bancaire' => $approCompteBancaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_compte_bancaire_show', methods: ['GET'])]
    public function show(ApproCompteBancaire $approCompteBancaire): Response
    {
        return $this->render('appro_compte_bancaire/show.html.twig', [
            'appro_compte_bancaire' => $approCompteBancaire,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_appro_compte_bancaire_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ApproCompteBancaire $approCompteBancaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ApproCompteBancaireForm::class, $approCompteBancaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_appro_compte_bancaire_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_compte_bancaire/edit.html.twig', [
            'appro_compte_bancaire' => $approCompteBancaire,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_compte_bancaire_delete', methods: ['POST'])]
    public function delete(Request $request, ApproCompteBancaire $approCompteBancaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$approCompteBancaire->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($approCompteBancaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_appro_compte_bancaire_index', [], Response::HTTP_SEE_OTHER);
    }
}
