<?php

namespace App\Controller;

use App\Entity\Caisse;
use App\Entity\CompteCaisse;
use App\Form\CaisseForm;
use App\Form\CompteCaisseForm;
use App\Repository\CaisseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/caisse')]
final class CaisseController extends AbstractController
{
    #[Route(name: 'app_caisse_index', methods: ['GET'])]
    public function index(CaisseRepository $caisseRepository): Response
    {
        return $this->render('caisse/index.html.twig', [
            'caisses' => $caisseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_caisse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $caisse = new Caisse();
        $form = $this->createForm(CaisseForm::class, $caisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($caisse);
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/new.html.twig', [
            'caisse' => $caisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_show', methods: ['GET', 'POST'])]
    public function show(Caisse $caisse, Request $request, EntityManagerInterface $entityManager): Response
    {
        $compteCaisse = new CompteCaisse();
        $compteCaisse->setCaisse($caisse);
        $compteCaisse->setSoldeInitial(0);
        $compteCaisse->setSoldeRestant(0);

        $formCompteCaisse = $this->createForm(CompteCaisseForm::class, $compteCaisse);
        $formCompteCaisse->handleRequest($request);

        if ($formCompteCaisse->isSubmitted() && $formCompteCaisse->isValid()) {
            // 👉 Vérifier si un compte existe déjà dans la même devise
            $devise = $compteCaisse->getDevise();

            $existing = $entityManager->getRepository(CompteCaisse::class)->findOneBy([
                'caisse' => $caisse,
                'devise' => $devise,
            ]);

            if ($existing) {
                $this->addFlash('warning', 'Un compte caisse avec la devise ' . $devise->getCodeIso() . ' existe déjà pour cette caisse.');
            } else {
                $entityManager->persist($compteCaisse);
                $entityManager->flush();

                $this->addFlash('success', 'Le compte caisse a été ajouté avec succès à la caisse ' . $caisse->getNom() . ' !');

                return $this->redirectToRoute('app_caisse_show', ['id' => $caisse->getId()], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('caisse/show.html.twig', [
            'caisse' => $caisse,
            'formCompteCaisse' => $formCompteCaisse->createView(),
        ]);
    }



    #[Route('/{id}/edit', name: 'app_caisse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Caisse $caisse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CaisseForm::class, $caisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('caisse/edit.html.twig', [
            'caisse' => $caisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_caisse_delete', methods: ['POST'])]
    public function delete(Request $request, Caisse $caisse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$caisse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($caisse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_caisse_index', [], Response::HTTP_SEE_OTHER);
    }
}
