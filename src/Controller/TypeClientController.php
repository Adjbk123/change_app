<?php

namespace App\Controller;

use App\Entity\TypeClient;
use App\Form\TypeClientForm;
use App\Repository\TypeClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/type-client')]
final class TypeClientController extends AbstractController
{
    #[Route(name: 'app_type_client_index', methods: ['GET'])]
    public function index(TypeClientRepository $typeClientRepository): Response
    {
        return $this->render('type_client/index.html.twig', [
            'type_clients' => $typeClientRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_type_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $typeClient = new TypeClient();
        $form = $this->createForm(TypeClientForm::class, $typeClient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($typeClient);
            $entityManager->flush();

            return $this->redirectToRoute('app_type_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_client/new.html.twig', [
            'type_client' => $typeClient,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_client_show', methods: ['GET'])]
    public function show(TypeClient $typeClient): Response
    {
        return $this->render('type_client/show.html.twig', [
            'type_client' => $typeClient,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_type_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TypeClient $typeClient, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TypeClientForm::class, $typeClient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_type_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('type_client/edit.html.twig', [
            'type_client' => $typeClient,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_type_client_delete', methods: ['POST'])]
    public function delete(Request $request, TypeClient $typeClient, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$typeClient->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($typeClient);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_type_client_index', [], Response::HTTP_SEE_OTHER);
    }
}
