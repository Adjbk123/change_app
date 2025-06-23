<?php

namespace App\Service;

use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface;
use Scheb\TwoFactorBundle\Mailer\AuthCodeMailerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class Custom2FAMailer implements AuthCodeMailerInterface
{
    private MailerInterface $mailer;
    private Environment $twig;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    public function sendAuthCode(TwoFactorInterface $user): void
    {
        $code = $user->getEmailAuthCode();
        $recipient = $user->getEmailAuthRecipient();

        $html = $this->twig->render('security/2fa_code_email.html.twig', [
            'code' => $code,
            'user' => $user
        ]);

        $email = (new Email())
            ->from('change@septetoilesconseilsolution.com')
            ->to($recipient)
            ->subject('Votre code de vérification ChangeMoney')
            ->html($html);

        $this->mailer->send($email);
    }
}
