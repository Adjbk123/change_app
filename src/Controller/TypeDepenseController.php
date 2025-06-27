<?php

namespace App\Controller;

use App\Entity\TypeDepense;
use App\Form\TypeDepenseForm;
use App\Repository\TypeDepenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/type-depense')]
final class TypeDepenseController extends AbstractController
{
    #[Route(name: 'app_type_depense_index', methods: ['GET'])]
    public function index(TypeDepenseRepository $typeDepenseRepository): Response
    {
        return $this->render('type_depense/index.html.twig', [
            'type_depenses' => $typeDepenseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_type_depense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $typeDepense = new TypeDepense();
        $form = $this->createForm(TypeDepenseForm::class, $typeDepense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($typeDepense);
            $entityManager->flush();

            return $this->redirectToRoute('app_type_depense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_depense/new.html.twig', [
            'type_depense' => $typeDepense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_depense_show', methods: ['GET'])]
    public function show(TypeDepense $typeDepense): Response
    {
        return $this->render('type_depense/show.html.twig', [
            'type_depense' => $typeDepense,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_depense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeDepense $typeDepense, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TypeDepenseForm::class, $typeDepense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_type_depense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_depense/edit.html.twig', [
            'type_depense' => $typeDepense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_depense_delete', methods: ['POST'])]
    public function delete(Request $request, TypeDepense $typeDepense, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$typeDepense->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($typeDepense);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_type_depense_index', [], Response::HTTP_SEE_OTHER);
    }
}
