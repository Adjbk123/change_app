<?php

namespace App\Controller;

use App\Entity\ApproAgence;
use App\Entity\CompteAgence;
use App\Entity\CompteGeneral;
use App\Form\ApproAgenceForm;
use App\Form\CompteAgenceForm;
use App\Repository\CompteAgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compte-agence')]
final class CompteAgenceController extends AbstractController
{
    #[Route(name: 'app_compte_agence_index', methods: ['GET'])]
    public function index(CompteAgenceRepository $compteAgenceRepository): Response
    {
        return $this->render('compte_agence/index.html.twig', [
            'compte_agences' => $compteAgenceRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_compte_agence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $compteAgence = new CompteAgence();
        $form = $this->createForm(CompteAgenceForm::class, $compteAgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($compteAgence);
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_agence/new.html.twig', [
            'compte_agence' => $compteAgence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_agence_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        CompteAgence $compteAgence,
        EntityManagerInterface $em
    ): Response {
        $appro = new ApproAgence();
        $form = $this->createForm(ApproAgenceForm::class, $appro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération du CompteGeneral selon la devise du CompteAgence
            $compteGeneral = $em->getRepository(CompteGeneral::class)->findOneBy([
                'devise' => $compteAgence->getDevise()
            ]);

            if (!$compteGeneral) {
                throw $this->createNotFoundException('Aucun compte général trouvé pour cette devise.');
            }

            $appro->setCompteAgence($compteAgence);
            $appro->setCompteGeneral($compteGeneral);  // Ici, on set bien le compte général
            $appro->setAgence($compteAgence->getAgence());
            $appro->setDevise($compteAgence->getDevise());
            $appro->setStatut('en_attente');
            $appro->setDemandeur($this->getUser());
            $appro->setDateDemande(new \DateTime());

            $em->persist($appro);
            $em->flush();

            $this->addFlash('success', 'Demande d\'approvisionnement enregistrée.');
            return $this->redirectToRoute('app_compte_agence_show', ['id' => $compteAgence->getId()]);
        }

        return $this->render('compte_agence/show.html.twig', [
            'compte_agence' => $compteAgence,
            'formApproAgence' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'app_compte_agence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CompteAgence $compteAgence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompteAgenceForm::class, $compteAgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_agence/edit.html.twig', [
            'compte_agence' => $compteAgence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_agence_delete', methods: ['POST'])]
    public function delete(Request $request, CompteAgence $compteAgence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$compteAgence->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($compteAgence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_compte_agence_index', [], Response::HTTP_SEE_OTHER);
    }
}
