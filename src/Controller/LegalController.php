<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LegalController extends AbstractController
{
    #[Route('/imprint', name: 'legal_imprint', methods: ['GET'])]
    public function imprint(): Response { return $this->render('pages/impressum.html.twig'); }

    #[Route('/privacy', name: 'legal_privacy', methods: ['GET'])]
    public function privacy(): Response { return $this->render('pages/datenschutz.html.twig'); }

    #[Route('/cookie-policy', name: 'legal_cookies', methods: ['GET'])]
    public function cookies(): Response { return $this->render('pages/cookie-richtlinie.html.twig'); }

    #[Route('/withdraw-contract', name: 'legal_withdrawal', methods: ['GET'])]
    public function withdrawal(): Response { return $this->render('pages/vertrag-widerrufen.html.twig'); }
}
