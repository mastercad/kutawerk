<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/areas/kuta-lounge', name: 'lounge_')]
final class LoungeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response { return $this->render('pages/bereiche__kuta-lounge.html.twig'); }

    #[Route('/rental', name: 'rental', methods: ['GET'])]
    public function rental(): Response { return $this->render('pages/bereiche__kuta-lounge__vermietung.html.twig'); }
}
