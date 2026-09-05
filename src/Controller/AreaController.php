<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/areas', name: 'area_')]
final class AreaController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response { return $this->render('pages/bereiche.html.twig'); }

    #[Route('/culture', name: 'culture', methods: ['GET'])]
    public function culture(): Response { return $this->render('pages/bereiche__kultur.html.twig'); }

    #[Route('/technology', name: 'technology', methods: ['GET'])]
    public function technology(): Response { return $this->render('pages/bereiche__technik.html.twig'); }
}
