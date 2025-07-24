<?php

namespace App\Controller;

use App\Entity\ApproCaisse;
use App\Entity\MouvementCaisse;
use App\Form\ApproCaisseForm;
use App\Repository\ApproCaisseRepository;
use App\Repository\AffectationAgenceRepository;
use App\Service\CaisseService;
use App\Service\FcmNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

#[Route('/appro-caisse')]
final class ApproCaisseController extends AbstractController
{
    private FcmNotificationService $fcmService;
    public function __construct(FcmNotificationService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    #[Route(name: 'app_appro_caisse_index', methods: ['GET'])]
    public function index(ApproCaisseRepository $approCaisseRepository, CaisseService $caisseService): Response
    {
        // On s'assure que l'utilisateur est bien connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $user = $this->getUser();
        $approvisionnements = [];

        // Cas 1 : Le responsable d'agence
        if ($this->isGranted('ROLE_RESPONSABLE')) {
            $agence = $user->getAgence(); // Suppose que votre entité User a une méthode getAgence()

            if (!$agence) {
                // Erreur de configuration : un responsable doit être lié à une agence
                throw new \LogicException('Cet utilisateur responsable n\'est associé à aucune agence.');
            }

            // On cherche les approvisionnements pour toutes les caisses de son agence
            $approvisionnements = $approCaisseRepository->findByAgence($agence);

        }
        // Cas 2 : Le caissier
        elseif ($this->isGranted('ROLE_CAISSE')) {
            $caisse = $caisseService->getCaisseAffectee($this->getUser()); // Suppose que votre entité User a une méthode getCaisse()

            if (!$caisse) {
                // Erreur de configuration : un caissier doit être lié à une caisse
                throw new \LogicException('Cet utilisateur caissier n\'est associé à aucune caisse.');
            }

            // On cherche les approvisionnements uniquement pour sa caisse
            $approvisionnements = $approCaisseRepository->findBy(['caisse' => $caisse], ['dateDemande' => 'DESC']);

        }
        // Cas 3 : L'administrateur (ou un rôle supérieur) voit tout
        elseif ($this->isGranted('ROLE_ADMIN')) {
            $approvisionnements = $approCaisseRepository->findBy([], ['dateDemande' => 'DESC']);
        }

        return $this->render('appro_caisse/index.html.twig', [
            'appro_caisses' => $approvisionnements,
        ]);
    }

    #[Route('/en-attente', name: 'app_appro_caisse_en_attente', methods: ['GET'])]
    public function enAttente(ApproCaisseRepository $approCaisseRepository): Response
    {
        $demandesEnAttente = $approCaisseRepository->findBy([
            'statut' => 'en_attente',
        ], ['dateDemande' => 'DESC']);

        return $this->render('appro_caisse/indexAttente.html.twig', [
            'appro_caisses' => $demandesEnAttente,
        ]);
    }
    #[Route('/valider/{id}', name: 'app_appro_caisse_valider', methods: ['POST'])]
    public function valider(
        Request $request,
        ApproCaisse $approCaisse,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Vérification CSRF token
        $token = new CsrfToken('valider_caisse' . $approCaisse->getId(), $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_compte_caisse_show', [
                'id' => $approCaisse->getCompteCaisse()->getId()
            ]);
        }

        // Vérifie que la demande est bien en attente
        if ($approCaisse->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');
            return $this->redirectToRoute('app_compte_caisse_show', [
                'id' => $approCaisse->getCompteCaisse()->getId()
            ]);
        }

        // Récupération des comptes concernés
        $montant = $approCaisse->getMontant();
        $compteCaisse = $approCaisse->getCompteCaisse();
        $compteAgence = $approCaisse->getCompteAgence();

        if (!$compteAgence) {
            $this->addFlash('error', 'Compte agence introuvable pour cette devise.');
            return $this->redirectToRoute('app_compte_caisse_show', [
                'id' => $compteCaisse->getId()
            ]);
        }

        // Vérification du solde disponible sur le compte agence
        $soldeAgenceActuel = $compteAgence->getSoldeRestant();
        if ($soldeAgenceActuel < $montant) {
            $this->addFlash('error', 'Solde insuffisant dans le compte agence.');
            return $this->redirectToRoute('app_compte_caisse_show', [
                'id' => $compteCaisse->getId()
            ]);
        }

        // MAJ des soldes
        $compteAgence->setSoldeRestant($soldeAgenceActuel - $montant);

        $compteCaisse->setSoldeInitial($compteCaisse->getSoldeInitial() + $montant);
        $compteCaisse->setSoldeRestant($compteCaisse->getSoldeRestant() + $montant);

        // MAJ de l'approvisionnement
        $approCaisse->setStatut('approuve');
        $approCaisse->setDateTraitement(new \DateTime());
        $approCaisse->setValidePar($this->getUser());
        $mouvement = new MouvementCaisse();
        $mouvement->setCompteCaisse($compteCaisse);
        $mouvement->setTypeMouvement('approvisionnement');
        $mouvement->setMontant($montant);
        $mouvement->setDevise($compteCaisse->getDevise());
        $mouvement->setEffectuePar($this->getUser());
        $mouvement->setDateMouvement(new \DateTimeImmutable());

        $em->persist($mouvement);
        // Enregistrement
        $em->flush();

        // Notification push au demandeur
        $demandeur = $approCaisse->getDemandeur();
        $fcmResult = null;
        if ($demandeur && $demandeur->getPushToken()) {
            $fcmResult = $this->fcmService->sendPush(
                $demandeur->getPushToken(),
                'Demande d\'approvisionnement validée',
                'Votre demande d\'approvisionnement caisse a été validée.',
                [
                    'type' => 'appro_caisse',
                    'approId' => $approCaisse->getId(),
                    'statut' => 'approuve',
                ]
            );
            file_put_contents(
                __DIR__.'/../../var/log/fcm.log',
                json_encode([
                    'to' => $demandeur->getPushToken(),
                    'title' => 'Demande d\'approvisionnement validée',
                    'body' => 'Votre demande d\'approvisionnement caisse a été validée.',
                    'data' => [
                        'type' => 'appro_caisse',
                        'approId' => $approCaisse->getId(),
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
        return $this->redirectToRoute('app_compte_caisse_show', [
            'id' => $compteCaisse->getId()
        ]);
    }

    #[Route('/rejeter/{id}', name: 'app_appro_caisse_rejeter', methods: ['POST'])]
    public function rejeter(
        Request $request,
        ApproCaisse $approCaisse,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        // Vérifiez le token CSRF
        $token = new CsrfToken('rejeter_caisse' . $approCaisse->getId(), $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_compte_caisse_show', ['id' => $approCaisse->getCompteCaisse()->getId()]);
        }

        // Vérifiez si l'approvisionnement est "en_attente"
        if ($approCaisse->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cette demande a déjà été traitée.');
            return $this->redirectToRoute('app_compte_caisse_show', ['id' => $approCaisse->getCompteCaisse()->getId()]);
        }

        // Logique de rejet
        $approCaisse->setStatut('rejete');
        $approCaisse->setDateTraitement(new \DateTime());
        $approCaisse->setValidePar($this->getUser()); // L'utilisateur connecté rejette

        $em->flush();

        // Notification push au demandeur
        $demandeur = $approCaisse->getDemandeur();
        $fcmResult = null;
        if ($demandeur && $demandeur->getPushToken()) {
            $fcmResult = $this->fcmService->sendPush(
                $demandeur->getPushToken(),
                'Demande d\'approvisionnement rejetée',
                'Votre demande d\'approvisionnement caisse a été rejetée.',
                [
                    'type' => 'appro_caisse',
                    'approId' => $approCaisse->getId(),
                    'statut' => 'rejete',
                ]
            );
            file_put_contents(
                __DIR__.'/../../var/log/fcm.log',
                json_encode([
                    'to' => $demandeur->getPushToken(),
                    'title' => 'Demande d\'approvisionnement rejetée',
                    'body' => 'Votre demande d\'approvisionnement caisse a été rejetée.',
                    'data' => [
                        'type' => 'appro_caisse',
                        'approId' => $approCaisse->getId(),
                        'statut' => 'rejete',
                    ],
                    'fcmResult' => $fcmResult,
                    'date' => date('c')
                ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n\n",
                FILE_APPEND
            );
            $this->addFlash('info', 'Résultat FCM : ' . json_encode($fcmResult));
        }

        $this->addFlash('success', 'Demande d\'approvisionnement caisse rejetée avec succès.');
        return $this->redirectToRoute('app_compte_caisse_show', ['id' => $approCaisse->getCompteCaisse()->getId()]);
    }

    #[Route('/new', name: 'app_appro_caisse_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AffectationAgenceRepository $affectationAgenceRepository, MailerInterface $mailer): Response
    {
        $approCaisse = new ApproCaisse();
        $form = $this->createForm(ApproCaisseForm::class, $approCaisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($approCaisse);
            $entityManager->flush();

            // Envoi d'un mail au responsable de l'agence
            $agence = $approCaisse->getCaisse()?->getAgence();
            if ($agence) {
                $affectation = $affectationAgenceRepository->findActiveResponsableByAgence($agence);
                $responsable = $affectation?->getUser();
                if ($responsable && $responsable->getEmail()) {
                    $email = (new TemplatedEmail())
                        ->from(new Address('contact@retouralasource-fx.com', 'MySwap'))
                        ->to($responsable->getEmail())
                        ->subject('Nouvelle demande d\'approvisionnement caisse')
                        ->htmlTemplate('emails/appro_caisse_demande.html.twig')
                        ->context([
                            'responsable' => $responsable,
                            'approCaisse' => $approCaisse,
                        ]);
                    $mailer->send($email);
                }
                // Notification push au responsable
                $fcmResult = null;
                if ($responsable && $responsable->getPushToken()) {
                    $fcmResult = $this->fcmService->sendPush(
                        $responsable->getPushToken(),
                        'Nouvelle demande d\'approvisionnement caisse',
                        'Une nouvelle demande d\'approvisionnement caisse a été créée.',
                        [
                            'type' => 'appro_caisse',
                            'approId' => $approCaisse->getId(),
                        ]
                    );
                    file_put_contents(
                        __DIR__.'/../../var/log/fcm.log',
                        json_encode([
                            'to' => $responsable->getPushToken(),
                            'title' => 'Nouvelle demande d\'approvisionnement caisse',
                            'body' => 'Une nouvelle demande d\'approvisionnement caisse a été créée.',
                            'data' => [
                                'type' => 'appro_caisse',
                                'approId' => $approCaisse->getId(),
                            ],
                            'fcmResult' => $fcmResult,
                            'date' => date('c')
                        ], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . "\n\n",
                        FILE_APPEND
                    );
                    $this->addFlash('info', 'Résultat FCM : ' . json_encode($fcmResult));
                }
            }

            return $this->redirectToRoute('app_appro_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_caisse/new.html.twig', [
            'appro_caisse' => $approCaisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_caisse_show', methods: ['GET'])]
    public function show(ApproCaisse $approCaisse): Response
    {
        return $this->render('appro_caisse/show.html.twig', [
            'appro_caisse' => $approCaisse,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_appro_caisse_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ApproCaisse $approCaisse, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ApproCaisseForm::class, $approCaisse);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_appro_caisse_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('appro_caisse/edit.html.twig', [
            'appro_caisse' => $approCaisse,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_appro_caisse_delete', methods: ['POST'])]
    public function delete(Request $request, ApproCaisse $approCaisse, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$approCaisse->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($approCaisse);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_appro_caisse_index', [], Response::HTTP_SEE_OTHER);
    }
}
