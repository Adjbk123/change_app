<?php

namespace App\Controller;

use App\Entity\AffectationAgence;
use App\Entity\User;
use App\Form\RegistrationForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $plainPassword = $form->get('plainPassword')->getData();

            // Récupère l'agence et le rôle interne depuis le formulaire
            $agence = $user->getAgence();
            $roleInternal = $form->get('role')->getData();
            $dateDebut = $form->get('date_debut')->getData();

            // Définir le rôle principal de l'utilisateur (pour la sécurité Symfony)
            $user->setRoles([$roleInternal]);
            $user->setIsActive(true);
            $user->setCreatedAt(new \DateTimeImmutable());

            // Encoder le mot de passe
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            // Ne pas flusher ici, nous le ferons après la création de AffectationAgence et potentiellement la mise à jour de l'Agence

            // Créer l'entité AffectationAgence
            $affectationAgence = new AffectationAgence();
            $affectationAgence->setUser($user);
            $affectationAgence->setAgence($agence);
            $affectationAgence->setRoleInterne($roleInternal);
            $affectationAgence->setDateDebut($dateDebut);
            $affectationAgence->setActif(true);

            $entityManager->persist($affectationAgence);

            // --- NOUVELLE LOGIQUE : Affecter l'utilisateur comme responsable de l'agence si le rôle est 'ROLE_USER' ---
            // Assurez-vous que 'ROLE_USER' est bien le rôle que vous utilisez pour les responsables d'agence
            if ($roleInternal === 'ROLE_RESPONSABLE') {
                // Vérifier si l'agence existe et a une méthode setResponsable
                if ($agence) {
                    // Vous devez vous assurer que votre entité Agence a bien une relation avec User
                    // et une méthode setResponsable() qui prend un User en paramètre.
                    $agence->setResponsable($user);
                    $entityManager->persist($agence); // Persister l'agence modifiée
                }
            }
            // --- FIN NOUVELLE LOGIQUE ---

            $entityManager->flush();

            $this->addFlash('success', 'Le compte utilisateur a été créé avec succès et affecté à l\'agence.');

            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
