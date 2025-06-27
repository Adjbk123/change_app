<?php

namespace App\Controller;

use App\Entity\Depense;
use App\Form\DepenseForm;
use App\Repository\CompteAgenceRepository;
use App\Repository\DepenseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/depense')]
final class DepenseController extends AbstractController
{
    #[Route(name: 'app_depense_index', methods: ['GET'])]
    public function index(DepenseRepository $depenseRepository): Response
    {

        $depenses = $depenseRepository->findBy(['agence'=>$this->getUser()->getAgence()]);

        if ($this->isGranted('ROLE_ADMIN')) {
            $depenses = $depenseRepository->findAll();
        }

        return $this->render('depense/index.html.twig', [
            'depenses' => $depenses,
        ]);
    }

    #[Route('/new', name: 'app_depense_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CompteAgenceRepository $compteAgenceRepository): Response
    {
        $depense = new Depense();
        $form = $this->createForm(DepenseForm::class, $depense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $agence = $this->getUser()->getAgence();
            $devise = $depense->getDevise();
            $montant = $depense->getMontant();

            $compteAgence = $compteAgenceRepository->findOneBy([
                'agence' => $agence,
                'devise' => $devise
            ]);

            // Sécurité : s'assurer que le compte existe
            if (!$compteAgence) {
                $this->addFlash('danger', "Aucun compte disponible pour cette devise.");
                return $this->redirectToRoute('app_depense_new');
            }

            // Sécurité : Vérifier qu'on a assez d'argent
            if ($compteAgence->getSoldeRestant() < $montant) {
                $this->addFlash('danger', "Fonds insuffisants pour effectuer cette dépense.");
                return $this->redirectToRoute('app_depense_new');
            }

            // Mise à jour du solde
            $compteAgence->setSoldeRestant($compteAgence->getSoldeRestant() - $montant);

            // Sauvegarde de la dépense
            $depense->setAgence($agence);
            $depense->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($depense);
            $entityManager->flush();

            $this->addFlash('success', "Dépense enregistrée avec succès.");
            return $this->redirectToRoute('app_depense_index');
        }

        return $this->render('depense/new.html.twig', [
            'depense' => $depense,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_depense_show', methods: ['GET'])]
    public function show(Depense $depense): Response
    {
        return $this->render('depense/show.html.twig', [
            'depense' => $depense,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_depense_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Depense $depense, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(DepenseForm::class, $depense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_depense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('depense/edit.html.twig', [
            'depense' => $depense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_depense_delete', methods: ['POST'])]
    public function delete(Request $request, Depense $depense, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$depense->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($depense);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_depense_index', [], Response::HTTP_SEE_OTHER);
    }
}
