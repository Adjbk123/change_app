<?php

namespace App\Controller;

use App\Entity\TauxChange;
use App\Form\TauxChangeForm;
use App\Repository\AffectationAgenceRepository;
use App\Repository\TauxChangeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/taux-change')]
final class TauxChangeController extends AbstractController
{
    #[Route(name: 'app_taux_change_index', methods: ['GET'])]
    public function index(
        TauxChangeRepository $tauxChangeRepository,
        AffectationAgenceRepository $affectationAgenceRepository,
        Security $security
    ): Response {
        $user = $security->getUser();
        $tauxChanges = [];

        // Récupération du rôle
        if ($this->isGranted('ROLE_RESPONSABLE') || $this->isGranted('ROLE_CAISSE')) {
            // Récupération de l'affectation active
            $affectation = $affectationAgenceRepository->findOneBy([
                'user' => $user,
                'actif' => true
            ]);

            if ($affectation && $agence = $affectation->getAgence()) {
                $tauxChanges = $tauxChangeRepository->findBy(
                    ['agence' => $agence],
                    ['dateDebut' => 'DESC']
                );
            } else {
                $this->addFlash('error', 'Aucune agence affectée trouvée pour l\'utilisateur.');
            }
        } else {
            // Admin ou autres rôles => accès complet
            $tauxChanges = $tauxChangeRepository->findBy([], ['dateDebut' => 'DESC']);
        }

        return $this->render('taux_change/index.html.twig', [
            'taux_changes' => $tauxChanges,
        ]);
    }

    #[Route('/new', name: 'app_taux_change_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        AffectationAgenceRepository $affectationRepository,
        TauxChangeRepository $tauxChangeRepository
    ): Response {
        $user = $this->getUser();

        $affectation = $affectationRepository->findOneBy([
            'user' => $user,
            'actif' => true,
        ]);

        if (!$affectation || !$affectation->getAgence()) {
            $this->addFlash('error', "Aucune agence liée à votre profil.");
            return $this->redirectToRoute('app_taux_change_index');
        }

        $agence = $affectation->getAgence();
        $tauxChange = new TauxChange();

        $form = $this->createForm(TauxChangeForm::class, $tauxChange);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Récupération des devises
            $deviseSource = $tauxChange->getDeviseSource();
            $deviseCible = $tauxChange->getDeviseCible();

            // On clôture l'ancien taux actif sur le même couple de devises
            $ancienTaux = $tauxChangeRepository->findOneBy([
                'agence' => $agence,
                'deviseSource' => $deviseSource,
                'deviseCible' => $deviseCible,
                'isActif' => true,
            ]);

            if ($ancienTaux) {
                $ancienTaux->setIsActif(false);
                $ancienTaux->setDateFin(new \DateTime());
                $em->persist($ancienTaux);
            }

            $tauxChange->setAgence($agence);
            $tauxChange->setDateDebut(new \DateTime());
            $tauxChange->setIsActif(true);
            $em->persist($tauxChange);
            $em->flush();

            $this->addFlash('success', 'Nouveau taux enregistré. L\'ancien taux a été clôturé.');
            return $this->redirectToRoute('app_taux_change_index');
        }

        return $this->render('taux_change/new.html.twig', [
            'taux_change' => $tauxChange,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_taux_change_show', methods: ['GET'])]
    public function show(TauxChange $tauxChange): Response
    {
        return $this->render('taux_change/show.html.twig', [
            'taux_change' => $tauxChange,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_taux_change_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TauxChange $tauxChange, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TauxChangeForm::class, $tauxChange);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_taux_change_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('taux_change/edit.html.twig', [
            'taux_change' => $tauxChange,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_taux_change_delete', methods: ['POST'])]
    public function delete(Request $request, TauxChange $tauxChange, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tauxChange->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tauxChange);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_taux_change_index', [], Response::HTTP_SEE_OTHER);
    }
}
