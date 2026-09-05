<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use App\Service\PreviewClock;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/events', name: 'events', methods: ['GET'])]
    public function index(EventRepository $events, PreviewClock $clock): Response
    {
        $today = $clock->now()->setTime(0, 0);

        return $this->render('site/events.html.twig', ['events' => $events->findUpcoming($today,$clock->now()),'previewAt'=>$clock->isPreview()?$clock->now():null]);
    }
}
