<?php

// src/Security/UserChecker.php
namespace App\Security;

use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (method_exists($user, 'isActive') && !$user->isActive()) {
            throw new CustomUserMessageAccountStatusException('Votre compte est désactivé. Veuillez contacter l\'administrateur.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Pas besoin ici, sauf si tu veux rajouter des logs ou un traitement après login
    }
}
