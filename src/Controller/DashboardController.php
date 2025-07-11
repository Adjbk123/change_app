<?php

namespace App\Controller;

use App\Service\CaisseService;
use App\Service\StatistiqueService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function indexA(): Response
    {
     return $this->redirectToRoute('app_dashboard');
    }
    #[Route('/dashboard', name: 'app_dashboard')]
    public function index(Security $security, StatistiqueService $statService, CaisseService $caisseService): Response
    {
        $user = $security->getUser();
        $roles = $user->getRoles();

        // Cas 1: Admin → Bilan global
        if ($this->isGranted('ROLE_ADMIN')) {
            $data = $statService->getGlobalStats();
            return $this->render('dashboard/index.html.twig', ['data' => $data]);
        }

        // Cas 2: Caissier → Bilan caisse uniquement
        if ($this->isGranted('ROLE_CAISSE')) {
            $caisse = $caisseService->getCaisseAffectee($user);
            $data = $statService->getStatsParCaisse($caisse);
          //  dd($data);
            return $this->render('dashboard/caissier.html.twig', ['data' => $data]);
        }

        // Cas 3: Responsable Agence → Bilan agence
        if ($this->isGranted('ROLE_RESPONSABLE')) {
            $agence = $user->getAgence();
            $data = $statService->getStatsParAgence($agence);
            //dd($data);
            return $this->render('dashboard/agence.html.twig', ['data' => $data]);
        }

        // Fallback si aucun rôle connu
        throw new AccessDeniedException("Accès non autorisé.");
    }

}
