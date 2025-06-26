<?php

// src/Security/UserChecker.php
namespace App\Security;

use App\Entity\User as AppUser;
use App\Repository\AffectationCaisseRepository;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    private AffectationCaisseRepository $affectationRepo;

    public function __construct(AffectationCaisseRepository $affectationRepo)
    {
        $this->affectationRepo = $affectationRepo;
    }

    /**
     * Vérifications exécutées juste après la récupération de l'utilisateur,
     * mais AVANT la validation du mot de passe.
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof AppUser) {
            return;
        }

        // 1. Vérification de base : le compte est-il actif ?
        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte est désactivé. Veuillez contacter l\'administrateur.');
        }

        // 2. Vérification métier : si c'est un caissier, a-t-il une caisse active ?
        if (in_array('ROLE_CAISSE', $user->getRoles(), true)) {

            $affectation = $this->affectationRepo->findActiveAffectationForUser($user);

            if (!$affectation) {
                // On bloque immédiatement la connexion.
                throw new CustomUserMessageAccountStatusException(
                    'Accès refusé. Vous n\'êtes affecté à aucune caisse active en ce moment.'
                );
            }
        }
    }

    /**
     * Cette méthode est maintenant vide, mais doit exister pour respecter l'interface.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        // Rien à faire ici.
    }
}
