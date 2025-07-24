<?php

namespace App\Controller;

use App\Entity\ApproAgence;
use App\Form\ApproAgenceForm;
use App\Repository\ApproAgenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use App\Service\FcmNotificationService;
use App\Repository\UserRepository;

#[Route('/appro-agence')]
final class ApproAgenceController extends AbstractController
{
    private FcmNotificationService $fcmService;
    private UserRepository $userRepository;
    public function __construct(FcmNotificationService $fcmService, UserRepository $userRepository)
    {
        $this->fcmService = $fcmService;
        $this->userRepository = $userRepository;
    }

    #[Route(name: 'app_appro_agence_index', methods: ['GET'])]
    public function index(ApproAgenceRepository $approAgenceRepository): Response
    {
        return $this->render('appro_agence/index.html.twig', [
            'appro_agences' => $approAgenceRepository->findAll(),
        ]);
    }

    #[Route('/en-attente', name: 'app_appro_agence_en_attente', methods: ['GET'])]
    public function enAttente(ApproAgenceRepository $approAgenceRepository): Response
    {
        $demandesEnAttente = $approAgenceRepository->findBy([
            'statut' => 'en_attente',
        ], ['dateDemande' => 'DESC']);

        return $this->render('appro_agence/indexAttente.html.twig', [
            'appro_agences' => $demandesEnAttente,
        ]);
    }
    #[Route('/valider/{id}', name: 'app_appro_agence_valider', methods: ['POST'])]
    public function valider(
        Request $request,
        ApproAgence $approAgence,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Vérification du token CSRF
        $token = new CsrfToken('valider' . $approAgence->getId(), $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_compte_agence_show', ['id' => $approAgence->getCompteAgence()->getId()]);
        }

        // Vérification statut
        if ($approAgence->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');
            return $this->redirectToRoute('app_compte_agence_show', ['id' => $approAgence->getCompteAgence()->getId()]);
        }

        $montant = $approAgence->getMontant();
        $compteAgence = $approAgence->getCompteAgence();
        $compteGeneral = $approAgence->getCompteGeneral();

        if (!$compteGeneral) {
            $this->addFlash('error', 'Compte général non trouvé pour cette devise. Validation impossible.');
            return $this->redirectToRoute('app_compte_agence_show', ['id' => $compteAgence->getId()]);
        }

        $em->getConnection()->beginTransaction(); // Démarre transaction

        try {
            // Calcul nouveau solde général
            $nouveauSoldeGeneral = $compteGeneral->getSoldeRestant() - $montant;
            if ($nouveauSoldeGeneral < 0) {
                $this->addFlash('danger', 'Solde insuffisant dans le compte général pour valider cette demande.');
                $em->getConnection()->rollBack();
                return $this->redirectToRoute('app_compte_agence_show', ['id' => $compteAgence->getId()]);
            }

            // Mise à jour du compte général (solde restant)
            $compteGeneral->setSoldeRestant($nouveauSoldeGeneral);

            // Mise à jour du compte agence (soldeInitial + soldeRestant)
            $compteAgence->setSoldeInitial($compteAgence->getSoldeInitial() + $montant);
            $compteAgence->setSoldeRestant($compteAgence->getSoldeRestant() + $montant);

            // Mise à jour de la demande d'approvisionnement
            $approAgence->setStatut('approuve');
            $approAgence->setDateTraitement(new \DateTime());
            $approAgence->setValidePar($this->getUser());

            // Persist toutes les modifs
            $em->persist($compteGeneral);
            $em->persist($compteAgence);
            $em->persist($approAgence);
            $em->flush();

            $em->getConnection()->commit(); // Commit transaction

            // Notification push au demandeur
            file_put_contents(__DIR__.'/../../var/log/debug_appro_agence.txt', 'TRY SEND PUSH ' . date('c') . "\n", FILE_APPEND);
            $demandeur = $approAgence->getDemandeur();
            $fcmResult = null;
            if ($demandeur && $demandeur->getPushToken()) {
                file_put_contents(__DIR__.'/../../var/log/debug_appro_agence.txt', 'HAS TOKEN: ' . $demandeur->getPushToken() . "\n", FILE_APPEND);
                $fcmResult = $this->fcmService->sendPush(
                    $demandeur->getPushToken(),
                    'Demande d\'approvisionnement validée',
                    'Votre demande d\'approvisionnement agence a été validée.',
                    [
                        'type' => 'appro_agence',
                        'approId' => $approAgence->getId(),
                        'statut' => 'approuve',
                    ]
                );
                file_put_contents(
                    __DIR__.'/../../var/log/fcm.log',
                    json_encode([
                        'to' => $demandeur->getPushToken(),
                        'title' => 'Demande d\'approvisionnement validée',
                        'body' => 'Votre demande d\'approvisionnement agence a été validée.',
                        'data' => [
                            'type' => 'appro_agence',
                            'approId' => $approAgence->getId(),
                            'statut' => 'approuve',
                        ],
                        'fcmResult' => $fcmResult,
                        'date' => date('c')
                    ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n\n",
                    FILE_APPEND
                );
                $this->addFlash('info', 'Résultat FCM : ' . json_encode($fcmResult));
            }

            $this->addFlash('success', 'Demande d\'approvisionnement validée avec succès.');
        } catch (\Exception $e) {
            $em->getConnection()->rollBack();
            $this->addFlash('error', 'Erreur lors de la validation : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_compte_agence_show', ['id' => $compteAgence->getId()]);
    }

    #[Route('/rejeter/{id}', name: 'app_appro_agence_rejeter', methods: ['POST'])]
    public function rejeter(
        Request $request,
        ApproAgence $approAgence,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Vérifiez le token CSRF
        $token = new CsrfToken('rejeter' . $approAgence->getId(), $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_compte_agence_show', ['id' => $approAgence->getCompteAgence()->getId()]);
        }

        // Vérifiez si l'approvisionnement est "en_attente"
        if ($approAgence->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');
            return $this->redirectToRoute('app_compte_agence_show', ['id' => $approAgence->getCompteAgence()->getId()]);
        }

        // Logique de rejet
        $approAgence->setStatut('rejete');
        $approAgence->setDateTraitement(new \DateTime());
        $approAgence->setValidePar($this->getUser()); // L'utilisateur connecté rejette

        $em->flush();

        // Notification push au demandeur
        file_put_contents(__DIR__.'/../../var/log/debug_appro_agence.txt', 'TRY SEND PUSH ' . date('c') . "\n", FILE_APPEND);
        $demandeur = $approAgence->getDemandeur();
        $fcmResult = null;
        if ($demandeur && $demandeur->getPushToken()) {
            file_put_contents(__DIR__.'/../../var/log/debug_appro_agence.txt', 'HAS TOKEN: ' . $demandeur->getPushToken() . "\n", FILE_APPEND);
            $fcmResult = $this->fcmService->sendPush(
                $demandeur->getPushToken(),
                'Demande d\'approvisionnement rejetée',
                'Votre demande d\'approvisionnement agence a été rejetée.',
                [
                    'type' => 'appro_agence',
                    'approId' => $approAgence->getId(),
                    'statut' => 'rejete',
                ]
            );
            file_put_contents(
                __DIR__.'/../../var/log/fcm.log',
                json_encode([
                    'to' => $demandeur->getPushToken(),
                    'title' => 'Demande d\'approvisionnement rejetée',
                    'body' => 'Votre demande d\'approvisionnement agence a été rejetée.',
                    'data' => [
                        'type' => 'appro_agence',
                        'approId' => $approAgence->getId(),
                        'statut' => 'rejete',
                    ],
                    'fcmResult' => $fcmResult,
                    'date' => date('c')
                ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n\n",
                FILE_APPEND
            );
            $this->addFlash('info', 'Résultat FCM : ' . json_encode($fcmResult));
        }

        $this->addFlash('success', 'Demande d\'approvisionnement rejetée avec succès.');
        return $this->redirectToRoute('app_compte_agence_show', ['id' => $approAgence->getCompteAgence()->getId()]);
    }

    #[Route('/new', name: 'app_appro_agence_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $approAgence = new ApproAgence();
        $form = $this->createForm(ApproAgenceForm::class, $approAgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($approAgence);
            $entityManager->flush();

            // Notification push à tous les admins
            $admins = $this->userRepository->findAdmins();
            foreach ($admins as $admin) {
                file_put_contents(__DIR__.'/../../var/log/debug_appro_agence.txt', 'TRY SEND PUSH ' . date('c') . "\n", FILE_APPEND);
                $fcmResult = null;
                if ($admin->getPushToken()) {
                    file_put_contents(__DIR__.'/../../var/log/debug_appro_agence.txt', 'HAS TOKEN: ' . $admin->getPushToken() . "\n", FILE_APPEND);
                    $fcmResult = $this->fcmService->sendPush(
                        $admin->getPushToken(),
                        'Nouvelle demande d\'approvisionnement agence',
                        'Une nouvelle demande d\'approvisionnement agence a été créée.',
                        [
                            'type' => 'appro_agence',
                            'approId' => $approAgence->getId(),
                        ]
                    );
                    file_put_contents(
                        __DIR__.'/../../var/log/fcm.log',
                        json_encode([
                            'to' => $admin->getPushToken(),
                            'title' => 'Nouvelle demande d\'approvisionnement agence',
                            'body' => 'Une nouvelle demande d\'approvisionnement agence a été créée.',
                            'data' => [
                                'type' => 'appro_agence',
                                'approId' => $approAgence->getId(),
                            ],
                            'fcmResult' => $fcmResult,
                            'date' => date('c')
                        ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n\n",
                        FILE_APPEND
                    );
                    $this->addFlash('info', 'Résultat FCM : ' . json_encode($fcmResult));
                }
            }

            return $this->redirectToRoute('app_appro_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_agence/new.html.twig', [
            'appro_agence' => $approAgence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_agence_show', methods: ['GET'])]
    public function show(ApproAgence $approAgence): Response
    {
        return $this->render('appro_agence/show.html.twig', [
            'appro_agence' => $approAgence,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_appro_agence_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ApproAgence $approAgence, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ApproAgenceForm::class, $approAgence);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_appro_agence_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_agence/edit.html.twig', [
            'appro_agence' => $approAgence,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_agence_delete', methods: ['POST'])]
    public function delete(Request $request, ApproAgence $approAgence, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$approAgence->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($approAgence);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_appro_agence_index', [], Response::HTTP_SEE_OTHER);
    }
}
