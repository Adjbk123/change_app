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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
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

            // --- NOUVELLE LOGIQUE ---
            if ($roleInternal === 'ROLE_ADMIN') {
                // Pas d'agence obligatoire, pas d'affectation créée
                $entityManager->flush();
                $email = (new TemplatedEmail())
                    ->from(new Address('contact@retouralasource-fx.com', 'MySwap'))
                    ->to($user->getEmail())
                    ->subject('Votre compte administrateur a été créé sur MySwap')
                    ->htmlTemplate('emails/registration_success.html.twig')
                    ->context([
                        'user' => $user,
                        'plainPassword' => $plainPassword,
                    ]);
                $mailer->send($email);
                $this->addFlash('success', 'Le compte administrateur a été créé avec succès. Un e-mail a été envoyé à l\'utilisateur.');
                return $this->redirectToRoute('app_user_index');
            }
            // Pour les autres rôles, l'agence est obligatoire
            if (!$agence) {
                $this->addFlash('danger', 'Veuillez sélectionner une agence pour ce rôle.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ]);
            }
            // Créer l'entité AffectationAgence
            $affectationAgence = new AffectationAgence();
            $affectationAgence->setUser($user);
            $affectationAgence->setAgence($agence);
            $affectationAgence->setRoleInterne($roleInternal);
            $affectationAgence->setDateDebut($dateDebut);
            $affectationAgence->setActif(true);
            $entityManager->persist($affectationAgence);

            // --- LOGIQUE RESPONSABLE ---
            if ($roleInternal === 'ROLE_RESPONSABLE') {
                if ($agence) {
                    $agence->setResponsable($user);
                    $entityManager->persist($agence);
                }
            }
            $entityManager->flush();
            $email = (new TemplatedEmail())
                ->from(new Address('contact@retouralasource-fx.com', 'MySwap'))
                ->to($user->getEmail())
                ->subject('Votre compte a été créé sur MySwap')
                ->htmlTemplate('emails/registration_success.html.twig')
                ->context([
                    'user' => $user,
                    'plainPassword' => $plainPassword,
                ]);
            $mailer->send($email);
            $this->addFlash('success', 'Le compte utilisateur a été créé avec succès et affecté à l\'agence. Un e-mail a été envoyé à l\'utilisateur.');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
