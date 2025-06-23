<?php

namespace App\Controller;

use App\Entity\ProfilClient;
use App\Form\ProfilClientForm;
use App\Repository\ProfilClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil-client')]
final class ProfilClientController extends AbstractController
{
    #[Route(name: 'app_profil_client_index', methods: ['GET'])]
    public function index(ProfilClientRepository $profilClientRepository): Response
    {
        return $this->render('profil_client/index.html.twig', [
            'profil_clients' => $profilClientRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_profil_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $profilClient = new ProfilClient();
        $form = $this->createForm(ProfilClientForm::class, $profilClient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($profilClient);
            $entityManager->flush();

            return $this->redirectToRoute('app_profil_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profil_client/new.html.twig', [
            'profil_client' => $profilClient,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_profil_client_show', methods: ['GET'])]
    public function show(ProfilClient $profilClient): Response
    {
        return $this->render('profil_client/show.html.twig', [
            'profil_client' => $profilClient,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_profil_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProfilClient $profilClient, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfilClientForm::class, $profilClient);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_profil_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profil_client/edit.html.twig', [
            'profil_client' => $profilClient,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_profil_client_delete', methods: ['POST'])]
    public function delete(Request $request, ProfilClient $profilClient, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$profilClient->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($profilClient);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_profil_client_index', [], Response::HTTP_SEE_OTHER);
    }
}
