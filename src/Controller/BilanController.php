<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class BilanController extends AbstractController
{
    #[Route('/bilan', name: 'app_bilan')]
    public function index(): Response
    {
        return $this->render('bilan/index.html.twig', [
            'controller_name' => 'BilanController',
        ]);
    }
}
