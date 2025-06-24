<?php

namespace App\Controller;

use App\Entity\Client;
use App\Entity\Devise;
use App\Entity\Operation;
use App\Entity\PieceIdentite;
use App\Entity\TypeClient;
use App\Form\OperationForm;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/operation')]
final class OperationController extends AbstractController
{
    #[Route(name: 'app_operation_index', methods: ['GET'])]
    public function index(OperationRepository $operationRepository): Response
    {
        return $this->render('operation/index.html.twig', [
            'operations' => $operationRepository->findAll(),
        ]);
    }

    #[Route('/new/{type}', name: 'app_operation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        string $type,
    ): Response {
        // --- LOGIQUE POUR LA MÉTHODE POST (soumission du formulaire principal) ---
        if ($request->isMethod('POST')) {
            // 1. Sécurité : Valider le jeton CSRF du formulaire principal
            $submittedToken = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('new_operation_token', $submittedToken)) {
                $this->addFlash('error', 'Jeton de sécurité invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_operation_new');
            }

            // 2. Récupérer les données brutes du formulaire
            $data = $request->request->all();

            // 3. Créer et hydrater l'entité Operation
            $operation = new Operation();
            $operation->setTypeOperation($data['typeOperation']);
            $operation->setCreatedAt(new \DateTimeImmutable());

            // Assumant que l'agent est l'utilisateur connecté
            if ($this->getUser()) {
                $operation->setAgent($this->getUser());
            }

            // 4. Charger les entités liées à partir des IDs reçus
            if (!empty($data['client'])) {
                $client = $entityManager->getRepository(Client::class)->find($data['client']);
                if ($client) {
                    $operation->setClient($client);
                }
            }

            if (!empty($data['deviseSource'])) {
                $deviseSource = $entityManager->getRepository(Devise::class)->find($data['deviseSource']);
                if ($deviseSource) {
                    $operation->setDeviseSource($deviseSource);
                }
            }

            if (!empty($data['deviseCible'])) {
                $deviseCible = $entityManager->getRepository(Devise::class)->find($data['deviseCible']);
                if ($deviseCible) {
                    $operation->setDeviseCible($deviseCible);
                }
            }

            // 5. Hydrater les montants, taux et autres champs. L'opérateur "null coalescent" (??) est très utile ici.
            $operation->setMontantSource($data['montantSource'] ?? null);
            $operation->setMontantCible($data['montantCible'] ?? null);
            $operation->setTaux($data['taux'] ?? null);

            // IMPORTANT : Ici, il est crucial d'ajouter une validation robuste des données !
            // Par exemple, pour un ACHAT, il faut vérifier que le client, les devises et les montants sont bien présents.
            // Vous pouvez utiliser le composant Validator de Symfony pour cela.

            $entityManager->persist($operation);
            $entityManager->flush();

            $this->addFlash('success', 'Opération enregistrée avec succès !');
            // Redirigez vers une page de listing ou de détail, par exemple.
            return $this->redirectToRoute('app_operation_index'); // Mettre la bonne route de redirection
        }

        // --- LOGIQUE POUR LA MÉTHODE GET (affichage de la page) ---
        // On charge toutes les données nécessaires pour le formulaire principal ET la modale.

        // Données pour le formulaire principal
        $clients = $entityManager->getRepository(Client::class)->findBy([], ['nom' => 'ASC']);
        $devises = $entityManager->getRepository(Devise::class)->findAll();

        // Données pour le formulaire de la MODALE
        $typeClients = $entityManager->getRepository(TypeClient::class)->findAll();
        $pieceIdentiteTypes = $entityManager->getRepository(PieceIdentite::class)->findAll();

        return $this->render('operation/new.html.twig', [
            'clients' => $clients,
            'devises' => $devises,
            'typeClients' => $typeClients,
            'pieceIdentiteTypes' => $pieceIdentiteTypes,
            'type'=>$type
        ]);
    }


    #[Route('/{id}', name: 'app_operation_show', methods: ['GET'])]
    public function show(Operation $operation): Response
    {
        return $this->render('operation/show.html.twig', [
            'operation' => $operation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_operation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OperationForm::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('operation/edit.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_operation_delete', methods: ['POST'])]
    public function delete(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$operation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($operation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
    }
}
