<?php
// src/Service/CompteClientService.php

namespace App\Service;

use App\Entity\ProfilClient; // MODIFIÉ : Importer ProfilClient
use App\Entity\CompteClient;
use App\Entity\Devise;
use App\Repository\CompteClientRepository;
use Doctrine\ORM\EntityManagerInterface;

class CompteClientService
{
    private EntityManagerInterface $entityManager;
    private CompteClientRepository $compteClientRepository;

    public function __construct(EntityManagerInterface $entityManager, CompteClientRepository $compteClientRepository)
    {
        $this->entityManager = $entityManager;
        $this->compteClientRepository = $compteClientRepository;
    }

    /**
     * Récupère ou crée un compte client pour une devise donnée, lié à un ProfilClient.
     */
    public function getOrCreateCompteClient(ProfilClient $profilClient, Devise $devise): CompteClient // MODIFIÉ
    {
        $compte = $this->compteClientRepository->findOneByProfilClientAndDevise($profilClient, $devise); // MODIFIÉ

        if (!$compte) {
            $compte = new CompteClient();
            $compte->setProfilClient($profilClient); // MODIFIÉ
            $compte->setDevise($devise);
            $compte->setSoldeInitial(0.0);
            $compte->setSoldeActuel(0.0);
            $this->entityManager->persist($compte);
        }
        return $compte;
    }

    /**
     * Dépose un montant sur un compte client.
     */
    public function deposer(CompteClient $compte, float $montant): void
    {
        $compte->deposer($montant);
        $this->entityManager->persist($compte);
    }

    /**
     * Retire un montant d'un compte client.
     */
    public function retirer(CompteClient $compte, float $montant): void
    {
        $compte->retirer($montant);
        $this->entityManager->persist($compte);
    }
}
