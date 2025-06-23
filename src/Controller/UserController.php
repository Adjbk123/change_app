<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/user')]
final class UserController extends AbstractController
{
    #[Route(name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
        ]);
    }
    #[Route('/user/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(User $user, Request $request, EntityManagerInterface $entityManager, CsrfTokenManagerInterface $csrfTokenManager): Response
    {
        // Validate CSRF token for security
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle' . $user->getId(), $token)) {
            $this->addFlash('error', 'Requête invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_user_index');
        }

        // Prevent deactivating the currently logged-in user (optional but recommended)
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            return $this->redirectToRoute('app_user_index');
        }

        $user->setIsActive(!$user->isActive()); // Toggle the status
        $entityManager->persist($user);
        $entityManager->flush();

        if ($user->isActive()) {
            $this->addFlash('success', sprintf('Le compte de %s a été activé avec succès.', $user->getNomComplet())); // Assuming getNomComplet() method
        } else {
            $this->addFlash('warning', sprintf('Le compte de %s a été désactivé.', $user->getNomComplet()));
        }

        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/reset-password', name: 'app_user_reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        User $user,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        // Vérifiez le jeton CSRF pour la sécurité
        if (!$this->isCsrfTokenValid('reset_password' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_user_index');
        }

        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if (empty($newPassword) || empty($confirmPassword)) {
            $this->addFlash('error', 'Veuillez saisir et confirmer le nouveau mot de passe.');
            return $this->redirectToRoute('app_user_index');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_user_index');
        }

        // Vous pouvez ajouter ici des règles de validation de complexité pour le mot de passe
        // Par exemple, longueur minimale, caractères spéciaux, etc.
        if (strlen($newPassword) < 8) {
            $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            return $this->redirectToRoute('app_user_index');
        }

        // Hashage du nouveau mot de passe
        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $newPassword
            )
        );

        $entityManager->flush();

        $this->addFlash('success', 'Le mot de passe de ' . $user->getNomComplet() . ' a été mis à jour avec succès.');

        return $this->redirectToRoute('app_user_index');
    }
    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
