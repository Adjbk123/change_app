<?php

namespace App\Service;

use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\TwoFactorFormRendererInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class Custom2FAFormRenderer implements TwoFactorFormRendererInterface
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function renderForm(Request $request, array $templateVars): Response
    {
        $content = $this->twig->render('security/2fa_form.html.twig', $templateVars);
        return new Response($content);
    }
}
