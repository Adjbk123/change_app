<?php

namespace App\Controller;

use App\Entity\ApproCaisse;
use App\Entity\CompteAgence;
use App\Entity\CompteCaisse;
use App\Form\ApproCaisseForm;
use App\Form\CompteCaisseForm;
use App\Repository\CompteCaisseRepository;
use App\Service\CaisseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/compte-caisse')]
final class CompteCaisseController extends AbstractController
{
    #[Route(name: 'app_compte_caisse_index', methods: ['GET'])]
    public function index(CompteCaisseRepository $compteCaisseRepository, CaisseService $caisseService): Response
    {
        $compteCaisses = $compteCaisseRepository->findAll();
        if ($this->isGranted('ROLE_CAISSE')) {
            $caisse = $caisseService->getCaisseAffectee($this->getUser());
            $compteCaisses = $compteCaisseRepository->findBy(['caisse'=>$caisse]);
        }
        return $this->render('compte_caisse/index.html.twig', [
            'compte_caisses' => $compteCaisseRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_compte_caisse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $compteCaisse = new CompteCaisse();
        $form = $this->createForm(CompteCaisseForm::class, $compteCaisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($compteCaisse);
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_caisse/new.html.twig', [
            'compte_caisse' => $compteCaisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_caisse_show', methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        CompteCaisse $compteCaisse,
        EntityManagerInterface $em
    ): Response {
        $appro = new ApproCaisse();
        $form = $this->createForm(ApproCaisseForm::class, $appro);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $appro->setCompteCaisse($compteCaisse);
            $appro->setCaisse($compteCaisse->getCaisse());
            $appro->setDevise($compteCaisse->getDevise());

            // Récupérer le CompteAgence associé à cette caisse
            $compteAgence = $em->getRepository(CompteAgence::class)->findOneBy([
                'agence' => $compteCaisse->getCaisse()->getAgence(),
                'devise' => $compteCaisse->getDevise()
            ]);
            if (!$compteAgence) {
                throw $this->createNotFoundException('Aucun compte agence trouvé pour cette caisse et devise.');
            }

            $appro->setCompteAgence($compteAgence);

            $appro->setStatut('en_attente');
            $appro->setDemandeur($this->getUser());
            $appro->setDateDemande(new \DateTime());

            $em->persist($appro);
            $em->flush();

            $this->addFlash('success', 'Demande enregistrée avec succès.');
            return $this->redirectToRoute('app_compte_caisse_show', ['id' => $compteCaisse->getId()]);
        }

        return $this->render('compte_caisse/show.html.twig', [
            'compte_caisse' => $compteCaisse,
            'formApproCaisse' => $form->createView(),
        ]);
    }


    #[Route('/{id}/edit', name: 'app_compte_caisse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CompteCaisse $compteCaisse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CompteCaisseForm::class, $compteCaisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_compte_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('compte_caisse/edit.html.twig', [
            'compte_caisse' => $compteCaisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_compte_caisse_delete', methods: ['POST'])]
    public function delete(Request $request, CompteCaisse $compteCaisse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$compteCaisse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($compteCaisse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_compte_caisse_index', [], Response::HTTP_SEE_OTHER);
    }
}
