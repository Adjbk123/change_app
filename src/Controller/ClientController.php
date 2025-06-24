<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\ClientDocument;
use App\Entity\CompteClient;
use App\Entity\Devise;
use App\Entity\PieceIdentite;
use App\Entity\ProfilClient;
use App\Entity\TypeClient;
use App\Form\ClientForm;
use App\Repository\ClientRepository;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/client')]
final class ClientController extends AbstractController
{

    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }
    #[Route(name: 'app_client_index', methods: ['GET'])]
    public function index(ClientRepository $clientRepository): Response
    {
        return $this->render('client/index.html.twig', [
            'clients' => $clientRepository->findAll(),
        ]);
    }

    #[Route('/new-ajax', name: 'app_client_new_ajax', methods: ['POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): JsonResponse { // <-- Le type de retour est maintenant toujours JsonResponse

        // On enveloppe toute la logique dans un bloc try...catch pour la robustesse
        try {
            $data = $request->request->all();
            $files = $request->files->all();

            // --- VALIDATION MINIMALE ---
            if (empty($data['client']['nom']) || empty($data['pieceIdentite']['type']) || !isset($files['pieceIdentite']['fichier'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Les champs Nom, Type de pièce et Fichier sont obligatoires.'
                ], 400); // 400 = Bad Request
            }

            // 1. Création du Client (logique inchangée)
            $client = new Client();
            $client->setNom($data['client']['nom']);
            $client->setPrenoms($data['client']['prenom'] ?? null);
            $client->setContact($data['client']['contact'] ?? null);
            $client->setCreatedBy($this->getUser());
            $client->setEmail($data['client']['email'] ?? null);
            $client->setProfession($data['client']['profession'] ?? null);
            $client->setCreatedAt(new DateTimeImmutable());
            if (!empty($data['client']['ifu'])) { $client->setIfu($data['client']['ifu']); }
            if (!empty($data['client']['registreCommerce'])) { $client->setRegistreCommerce($data['client']['registreCommerce']); }
            $entityManager->persist($client);
            $entityManager->flush(); // Flush pour obtenir l'ID

            // 2. Attribution du ProfilClient (logique inchangée)
            $selectedTypeClientId = $data['client']['typeClient'] ?? null;
            if ($selectedTypeClientId) {
                $typeClient = $entityManager->getRepository(TypeClient::class)->find($selectedTypeClientId);
                if ($typeClient) {
                    $profilClient = new ProfilClient();
                    $profilClient->setClient($client);
                    $profilClient->setTypeClient($typeClient);
                    $profilClient->setNumeroProfilCompte('PROF-' . $client->getId() . '-' . (new DateTimeImmutable())->format('YmdHis'));
                    $profilClient->setIsActif(true);
                    $profilClient->setCreatedAt(new DateTimeImmutable());
                    $entityManager->persist($profilClient);
                }
            }

            // 3. Gestion de la pièce d'identité (logique inchangée)
            $pieceData = $data['pieceIdentite'];
            $uploadedFile = $files['pieceIdentite']['fichier'];
            $pieceIdentiteType = $entityManager->getRepository(PieceIdentite::class)->find($pieceData['type']);

            $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

            $uploadedFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/piece', $newFilename);

            $clientDocument = new ClientDocument();
            $clientDocument->setClient($client);
            $clientDocument->setPieceIdentite($pieceIdentiteType);
            $clientDocument->setFichier($newFilename);
            $clientDocument->setNumero($pieceData['numero']);
            $clientDocument->setDateUpload(new DateTime());
            $clientDocument->setIsActif(true);
            $dateEmission = new DateTime($pieceData['dateEmission']);
            $anneesValidite = (int)($pieceData['anneesValidite'] ?? 0);
            if ($anneesValidite > 0) {
                $dateExpiration = (clone $dateEmission)->modify('+' . $anneesValidite . ' years');
                $clientDocument->setDateEmission($dateEmission);
                $clientDocument->setDateExpiration($dateExpiration);
            }
            $entityManager->persist($clientDocument);

            // Flush final
            $entityManager->flush();

            // --- C'est LA SEULE RÉPONSE en cas de succès ---
            return new JsonResponse([
                'success' => true,
                'message' => 'Client créé avec succès !',
                'client' => [
                    'id' => $client->getId(),
                    'nomComplet' => $client->getNom() . ' ' . $client->getPrenoms()
                ]
            ]);

        } catch (\Exception $e) {
            // --- C'est LA SEULE RÉPONSE en cas d'erreur ---
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500); // 500 = Internal Server Error
        }
    }

    #[Route('/new', name: 'app_client_new', methods: ['GET', 'POST'])]
    public function newAjax(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer les données pour les listes déroulantes
        $typeClients = $entityManager->getRepository(TypeClient::class)->findAll();
        $pieceIdentiteTypes = $entityManager->getRepository(PieceIdentite::class)->findAll();

        if ($request->isMethod('POST')) {
            $data = $request->request->all();
            $files = $request->files->all();

            // 1. Création d'un nouveau Client
            $client = new Client();
            $client->setNom($data['client']['nom'] ?? null);
            $client->setPrenoms($data['client']['prenom'] ?? null); // Assurez-vous que c'est bien setPrenoms
            $client->setContact($data['client']['contact'] ?? null);
            $client->setEmail($data['client']['email'] ?? null);
            $client->setProfession($data['client']['profession'] ?? null);
            $client->setCreatedAt(new DateTimeImmutable());

            // Récupérer les champs spécifiques à l'entreprise si présents
            // Assurez-vous que ces setters (setIfu, setRegistreCommerce) existent dans votre entité Client
            if (isset($data['client']['ifu'])) {
                $client->setIfu($data['client']['ifu']);
            }
            if (isset($data['client']['registreCommerce'])) {
                $client->setRegistreCommerce($data['client']['registreCommerce']);
            }

            $entityManager->persist($client);
            $entityManager->flush();

            // 2. Attribution du ProfilClient
            $selectedTypeClientId = $data['client']['typeClient'] ?? null;
            if ($selectedTypeClientId) {
                $typeClient = $entityManager->getRepository(TypeClient::class)->find($selectedTypeClientId);
                if ($typeClient) {
                    $profilClient = new ProfilClient();
                    $profilClient->setClient($client);
                    $profilClient->setTypeClient($typeClient);
                    $profilClient->setNumeroProfilCompte('PROF-' . $client->getId() . '-' . (new DateTimeImmutable())->format('YmdHis'));
                    $profilClient->setIsActif(true);
                    $profilClient->setCreatedAt(new DateTimeImmutable());
                    $entityManager->persist($profilClient);
                }
            }
            $entityManager->flush();

            // 3. Gestion de la pièce d'identité unique et de son ClientDocument
            if (isset($data['pieceIdentite']) && isset($files['pieceIdentite']['fichier'])) {
                $pieceData = $data['pieceIdentite'];
                $uploadedFile = $files['pieceIdentite']['fichier'];

                $pieceIdentiteType = $entityManager->getRepository(PieceIdentite::class)->find($pieceData['type'] ?? null);

                if ($pieceIdentiteType && $uploadedFile->isValid()) {
                    $originalFilename = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $this->slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $uploadedFile->guessExtension();

                    try {
                        // Déplacer le fichier dans 'public/uploads/piece'
                        $uploadedFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/piece',
                            $newFilename
                        );

                        $clientDocument = new ClientDocument();
                        $clientDocument->setClient($client);
                        $clientDocument->setPieceIdentite($pieceIdentiteType);
                        $clientDocument->setFichier($newFilename);
                        $clientDocument->setDateUpload(new DateTime());
                        $clientDocument->setIsActif(true);

                        // Récupérer la date d'émission du formulaire
                        $dateEmissionStr = $pieceData['dateEmission'] ?? null;
                        $dateEmission = null;
                        if ($dateEmissionStr) {
                            $dateEmission = new DateTime($dateEmissionStr);
                        }

                        // Calculer la date d'expiration
                        $anneesValidite = (int)($pieceData['anneesValidite'] ?? 0);
                        if ($dateEmission && $anneesValidite > 0) {
                            $dateExpiration = (clone $dateEmission)->modify('+' . $anneesValidite . ' years');
                            $clientDocument->setDateEmission($dateEmission); // Si vous voulez aussi stocker la date d'émission
                            $clientDocument->setDateExpiration($dateExpiration); // Assurez-vous d'avoir ce setter dans ClientDocument
                        }

                        $entityManager->persist($clientDocument);

                    } catch (FileException $e) {
                        $this->addFlash('error', 'Erreur lors de l\'upload du fichier de la pièce d\'identité : ' . $e->getMessage());
                    } catch (\Exception $e) {
                        $this->addFlash('error', 'Erreur lors du traitement de la pièce d\'identité : ' . $e->getMessage());
                    }
                } else {
                    $this->addFlash('error', 'Veuillez sélectionner un type de pièce d\'identité et un fichier valide.');
                }
            } else {
                $this->addFlash('error', 'La pièce d\'identité est obligatoire.');
            }

            $entityManager->flush();

            // Le message de succès sera affiché via SweetAlert2
            $this->addFlash('success', 'Client créé avec succès !');

            return $this->redirectToRoute('app_client_index');
        }

        return $this->render('client/new.html.twig', [
            'typeClients' => $typeClients,
            'pieceIdentiteTypes' => $pieceIdentiteTypes,
        ]);
    }
    #[Route('/{id}', name: 'app_client_show', methods: ['GET'])]
    public function show(Client $client): Response
    {
        return $this->render('client/show.html.twig', [
            'client' => $client,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClientForm::class, $client);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/edit.html.twig', [
            'client' => $client,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_client_delete', methods: ['POST'])]
    public function delete(Request $request, Client $client, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$client->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($client);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_client_index', [], Response::HTTP_SEE_OTHER);
    }
}
