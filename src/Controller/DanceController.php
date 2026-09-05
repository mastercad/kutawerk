<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\TrainingSessionRepository;
use App\Repository\UserRepository;
use App\Repository\CourseRepository;
use App\Repository\LocationRepository;
use App\Service\PreviewClock;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/areas/dance', name: 'dance_')]
final class DanceController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response { return $this->render('pages/bereiche__tanz.html.twig'); }

    #[Route('/trainers', name: 'trainers', methods: ['GET'])]
    public function trainers(UserRepository $users, CourseRepository $courses): Response
    {
        $trainers = $users->findTrainers();
        $profiles = [];
        foreach ($trainers as $trainer) {
            $intro = [];
            $biography = [];
            $biographyStarted = false;
            $paragraphs = preg_split('/\R\s*\R/u', trim((string) $trainer->getTrainerBio())) ?: [];
            foreach ($paragraphs as $paragraph) {
                $paragraph = trim($paragraph);
                if ($paragraph === '') continue;
                if (!$biographyStarted && mb_strlen($paragraph) <= 60) {
                    $intro[] = $paragraph;
                    continue;
                }
                $biographyStarted = true;
                $biography[] = $paragraph;
            }
            $blocks = [];
            foreach ($biography as $paragraph) {
                if (str_starts_with($paragraph, '• ')) {
                    $last = array_key_last($blocks);
                    if ($last === null || $blocks[$last]['type'] !== 'list') $blocks[] = ['type' => 'list', 'items' => []];
                    $blocks[array_key_last($blocks)]['items'][] = mb_substr($paragraph, 2);
                } else {
                    $blocks[] = ['type' => 'text', 'text' => $paragraph];
                }
            }
            $profiles[$trainer->getId()] = ['intro' => $intro, 'blocks' => $blocks];
        }

        return $this->render('pages/bereiche__tanz__trainer.html.twig', [
            'trainers' => $trainers,
            'profiles' => $profiles,
            'courses' => $courses->findBy(['active' => true], ['name' => 'ASC']),
        ]);
    }

    #[Route('/schedule', name: 'schedule', methods: ['GET'])]
    public function schedule(TrainingSessionRepository $sessions, LocationRepository $locations, PreviewClock $clock): Response
    {
        $overview = $sessions->findOverview($clock->now()->setTime(0,0));

        return $this->render('training/schedule.html.twig', ['sessions' => $overview, 'locations' => $locations->findBy(['active' => true], ['name' => 'ASC']),'previewAt'=>$clock->isPreview()?$clock->now():null]);
    }

    #[Route('/dates', name: 'dates', methods: ['GET'])]
    public function dates(EventRepository $events, PreviewClock $clock): Response
    {
        $today = $clock->now()->setTime(0,0);

        return $this->render('dance/dates.html.twig', [
            'events' => $events->findUpcomingByDepartmentSlug($today, 'dance',$clock->now()),
            'pastEvents' => $events->findPastByDepartmentSlug($today, 'dance',$clock->now()),
            'previewAt' => $clock->isPreview()?$clock->now():null,
        ]);
    }

    #[Route('/membership', name: 'contract', methods: ['GET'])]
    public function contract(): Response { return $this->render('pages/bereiche__tanz__vertrag.html.twig'); }
}
