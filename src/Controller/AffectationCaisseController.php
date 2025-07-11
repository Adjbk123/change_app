<?php

namespace App\Controller;

use App\Entity\AffectationCaisse;
use App\Form\AffectationCaisseForm;
use App\Repository\AffectationCaisseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/affectation-caisse')]
final class AffectationCaisseController extends AbstractController
{
    #[Route('/', name: 'app_affectation_caisse_index', methods: ['GET'])]
    public function index(AffectationCaisseRepository $affectationCaisseRepository, UserInterface $user = null): Response
    {
        $affectationCaisses = [];

        // Vérifiez si l'utilisateur est connecté et a le rôle 'ROLE_RESPONSABLE'
        if ($user && $this->isGranted('ROLE_RESPONSABLE')) {

            if (method_exists($user, 'getAgence') && $user->getAgence()) {
                $agence = $user->getAgence();

                $affectationCaisses = $affectationCaisseRepository->findByAgenceCaisses($agence);
            } else {
                // Gérer le cas où le responsable n'a pas d'agence associée (optionnel)
                $this->addFlash('warning', 'Votre compte responsable n\'est pas associé à une agence.');
            }
        } else {

            $affectationCaisses = $affectationCaisseRepository->findAll();
        }

        return $this->render('affectation_caisse/index.html.twig', [
            'affectation_caisses' => $affectationCaisses,
        ]);
    }

    #[Route('/new', name: 'app_affectation_caisse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $affectationCaisse = new AffectationCaisse();
        $form = $this->createForm(AffectationCaisseForm::class, $affectationCaisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $affectationCaisse->setIsActive(1);
            $affectationCaisse->setDateDebut(new \DateTime());
            $entityManager->persist($affectationCaisse);
            $entityManager->flush();

            return $this->redirectToRoute('app_affectation_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('affectation_caisse/new.html.twig', [
            'affectation_caisse' => $affectationCaisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/deactivate', name: 'app_affectation_caisse_deactivate', methods: ['POST'])]
    public function deactivate(Request $request, AffectationCaisse $affectationCaisse, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le jeton CSRF pour la sécurité
        if ($this->isCsrfTokenValid('deactivate' . $affectationCaisse->getId(), $request->request->get('_token'))) {
            $affectationCaisse->setIsActive(false); // Passe isActive à false
            $affectationCaisse->setDateFin(new \DateTime()); // Enregistre la date de fin
            $entityManager->flush();

            $this->addFlash('success', 'La session a été clôturée avec succès.');
        } else {
            $this->addFlash('error', 'Token CSRF invalide.');
        }

        return $this->redirectToRoute('app_affectation_caisse_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_affectation_caisse_show', methods: ['GET'])]
    public function show(AffectationCaisse $affectationCaisse): Response
    {
        return $this->render('affectation_caisse/show.html.twig', [
            'affectation_caisse' => $affectationCaisse,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_affectation_caisse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AffectationCaisse $affectationCaisse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AffectationCaisseForm::class, $affectationCaisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_affectation_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('affectation_caisse/edit.html.twig', [
            'affectation_caisse' => $affectationCaisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_affectation_caisse_delete', methods: ['POST'])]
    public function delete(Request $request, AffectationCaisse $affectationCaisse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$affectationCaisse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($affectationCaisse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_affectation_caisse_index', [], Response::HTTP_SEE_OTHER);
    }
}
