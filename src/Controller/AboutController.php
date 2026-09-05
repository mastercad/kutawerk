<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AboutController extends AbstractController
{
    #[Route('/about-us', name: 'about', methods: ['GET'])]
    public function index(): Response { return $this->render('pages/ueber-uns.html.twig'); }

    #[Route('/about-us/downloads', name: 'about_downloads', methods: ['GET'])]
    public function downloads(): Response { return $this->render('pages/ueber-uns__downloads.html.twig'); }
}
