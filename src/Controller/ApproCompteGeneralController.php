<?php

namespace App\Controller;

use App\Entity\ApproCompteGeneral;
use App\Form\ApproCompteGeneralForm;
use App\Repository\ApproCompteGeneralRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/appro-compte-general')]
final class ApproCompteGeneralController extends AbstractController
{
    #[Route(name: 'app_appro_compte_general_index', methods: ['GET'])]
    public function index(ApproCompteGeneralRepository $approCompteGeneralRepository): Response
    {
        return $this->render('appro_compte_general/index.html.twig', [
            'appro_compte_generals' => $approCompteGeneralRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_appro_compte_general_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $approCompteGeneral = new ApproCompteGeneral();
        $form = $this->createForm(ApproCompteGeneralForm::class, $approCompteGeneral);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($approCompteGeneral);
            $entityManager->flush();

            return $this->redirectToRoute('app_appro_compte_general_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_compte_general/new.html.twig', [
            'appro_compte_general' => $approCompteGeneral,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_compte_general_show', methods: ['GET'])]
    public function show(ApproCompteGeneral $approCompteGeneral): Response
    {
        return $this->render('appro_compte_general/show.html.twig', [
            'appro_compte_general' => $approCompteGeneral,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_appro_compte_general_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ApproCompteGeneral $approCompteGeneral, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ApproCompteGeneralForm::class, $approCompteGeneral);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_appro_compte_general_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_compte_general/edit.html.twig', [
            'appro_compte_general' => $approCompteGeneral,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_compte_general_delete', methods: ['POST'])]
    public function delete(Request $request, ApproCompteGeneral $approCompteGeneral, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$approCompteGeneral->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($approCompteGeneral);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_appro_compte_general_index', [], Response::HTTP_SEE_OTHER);
    }
}
