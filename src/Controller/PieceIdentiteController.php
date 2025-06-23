<?php

namespace App\Controller;

use App\Entity\PieceIdentite;
use App\Form\PieceIdentiteForm;
use App\Repository\PieceIdentiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/piece-identite')]
final class PieceIdentiteController extends AbstractController
{
    #[Route(name: 'app_piece_identite_index', methods: ['GET'])]
    public function index(PieceIdentiteRepository $pieceIdentiteRepository): Response
    {
        return $this->render('piece_identite/index.html.twig', [
            'piece_identites' => $pieceIdentiteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_piece_identite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $pieceIdentite = new PieceIdentite();
        $form = $this->createForm(PieceIdentiteForm::class, $pieceIdentite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($pieceIdentite);
            $entityManager->flush();

            return $this->redirectToRoute('app_piece_identite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('piece_identite/new.html.twig', [
            'piece_identite' => $pieceIdentite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_piece_identite_show', methods: ['GET'])]
    public function show(PieceIdentite $pieceIdentite): Response
    {
        return $this->render('piece_identite/show.html.twig', [
            'piece_identite' => $pieceIdentite,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_piece_identite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PieceIdentite $pieceIdentite, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PieceIdentiteForm::class, $pieceIdentite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_piece_identite_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('piece_identite/edit.html.twig', [
            'piece_identite' => $pieceIdentite,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_piece_identite_delete', methods: ['POST'])]
    public function delete(Request $request, PieceIdentite $pieceIdentite, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$pieceIdentite->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($pieceIdentite);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_piece_identite_index', [], Response::HTTP_SEE_OTHER);
    }
}
