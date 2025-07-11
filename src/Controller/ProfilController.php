<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\UserForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserForm::class, $user);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $avatarFile */
            $avatarFile = $form->get('avatar')->getData();
            if ($avatarFile) {
                $filename = uniqid('avatar_').'.'.$avatarFile->guessExtension();
                $avatarFile->move($this->getParameter('kernel.project_dir').'/public/uploads/avatar', $filename);
                $user->setAvatar('/uploads/avatar/'.$filename);
            }
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès.');
            return $this->redirectToRoute('app_profil');
        }
        return $this->render('profil/index.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
