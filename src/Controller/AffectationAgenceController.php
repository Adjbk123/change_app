<?php

namespace App\Controller;

use App\Entity\AffectationAgence;
use App\Form\AffectationAgenceForm;
use App\Repository\AffectationAgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/affectation')]
final class AffectationAgenceController extends AbstractController
{
    #[Route(name: 'app_affectation_agence_index', methods: ['GET'])]
    public function index(AffectationAgenceRepository $affectationAgenceRepository): Response
    {
        return $this->render('affectation_agence/index.html.twig', [
            'affectation_agences' => $affectationAgenceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_affectation_agence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $affectationAgence = new AffectationAgence();
        $form = $this->createForm(AffectationAgenceForm::class, $affectationAgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($affectationAgence);
            $entityManager->flush();

            return $this->redirectToRoute('app_affectation_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('affectation_agence/new.html.twig', [
            'affectation_agence' => $affectationAgence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_affectation_agence_show', methods: ['GET'])]
    public function show(AffectationAgence $affectationAgence): Response
    {
        return $this->render('affectation_agence/show.html.twig', [
            'affectation_agence' => $affectationAgence,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_affectation_agence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AffectationAgence $affectationAgence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AffectationAgenceForm::class, $affectationAgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_affectation_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('affectation_agence/edit.html.twig', [
            'affectation_agence' => $affectationAgence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_affectation_agence_delete', methods: ['POST'])]
    public function delete(Request $request, AffectationAgence $affectationAgence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$affectationAgence->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($affectationAgence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_affectation_agence_index', [], Response::HTTP_SEE_OTHER);
    }
}
