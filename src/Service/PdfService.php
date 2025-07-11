<?php
// src/Service/PdfService.php

namespace App\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
// use Symfony\Component\Filesystem\Filesystem; // Si vous n'utilisez pas Filesystem, vous pouvez supprimer cette ligne

class PdfService
{
    private Environment $twig;
    // private string $projectDir; // Supprimez cette ligne si vous ne voulez pas de l'argument

    public function __construct(Environment $twig) // <-- Le constructeur n'a plus que $twig
    {
        $this->twig = $twig;
        // $this->projectDir = $projectDir; // Supprimez cette ligne
    }

    /**
     * Génère un PDF à partir d'un template Twig.
     */
    public function generatePdf(
        string $template,
        array $data = [],
        float $width = 595.28,     // ≈ A4 width in pt (210mm)
        float $height = 841.89     // ≈ A4 height in pt (297mm)
    ): string {
        $html = $this->twig->render($template, $data);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $width, $height]); // ✅ Dimensions personnalisées
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Génère un PDF et le sauvegarde dans /public/uploads/recu, puis retourne une réponse de téléchargement.
     */
    public function streamPdf(
        string $template,
        array $data = [],
        string $filename = 'document.pdf',
        float $width = 595.28,
        float $height = 841.89
    ): BinaryFileResponse {
        $pdfContent = $this->generatePdf($template, $data, $width, $height);

        $directory = 'uploads/recu';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = $directory . '/' . $filename;
        file_put_contents($filePath, $pdfContent);

        $response = new BinaryFileResponse($filePath);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_INLINE,
            $filename
        );

        return $response;
    }

    public function savePdf(
        string $template,
        array $data = [],
        string $filename = 'document.pdf',
        float $width = 595.28,
        float $height = 841.89
    ): string {
        $pdfContent = $this->generatePdf($template, $data, $width, $height);

        // Récupère le chemin absolu vers le dossier public
        $publicPath = realpath(__DIR__ . '/../../public');

        if ($publicPath === false) {
            throw new \RuntimeException("Impossible de localiser le dossier public.");
        }

        $uploadDir = $publicPath . '/uploads/recu';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $filePath = $uploadDir . '/' . $filename;
        file_put_contents($filePath, $pdfContent);

        // Retourne le chemin relatif public (pour affichage via navigateur)
        return 'uploads/recu/' . $filename;
    }

}
