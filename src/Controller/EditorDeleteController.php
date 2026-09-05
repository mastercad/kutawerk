<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\{Course, Event, Location, NewsPost, TrainingSession, User};
use App\Repository\TrainingSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/editor', name: 'editor_delete_')]
#[IsGranted('ROLE_USER')]
final class EditorDeleteController extends AbstractController
{
    #[Route('/news/{id}/delete', name: 'news', methods: ['POST'])]
    public function news(Request $request, NewsPost $post, EntityManagerInterface $em): Response
    {
        $this->requirePermission(User::PERMISSION_NEWS);
        $this->csrf($request, 'news-delete-'.$post->getId());
        $post->setPublished(false);
        $em->flush();
        $this->addFlash('success', 'Der News-Beitrag wurde aus der öffentlichen Ansicht entfernt.');

        return $this->redirectToRoute('editor_news');
    }

    #[Route('/events/{id}/delete', name: 'event', methods: ['POST'])]
    public function event(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        $user = $this->requirePermission(User::PERMISSION_EVENTS);
        if (!$this->isAdmin($user) && (!$event->getDepartment() || !$user->getDepartments()->contains($event->getDepartment()))) {
            throw $this->createAccessDeniedException();
        }
        $this->csrf($request, 'event-delete-'.$event->getId());
        $event->setActive(false);
        $em->flush();
        $this->addFlash('success', 'Der Termin wurde deaktiviert.');

        return $this->redirectToRoute('editor_events');
    }

    #[Route('/training-times/{id}/delete', name: 'training', methods: ['POST'])]
    public function training(Request $request, TrainingSession $session, EntityManagerInterface $em): Response
    {
        $user = $this->requirePermission(User::PERMISSION_TRAINING);
        if (!$this->isAdmin($user) && !$session->getCourse()->getTrainers()->contains($user)) {
            throw $this->createAccessDeniedException();
        }
        $this->csrf($request, 'training-delete-'.$session->getId());
        $session->setActive(false);
        $em->flush();
        $this->addFlash('success', 'Die Trainingszeit wurde deaktiviert.');

        return $this->redirectToRoute('editor_training_times');
    }

    #[Route('/locations/{id}/delete', name: 'location', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function location(Request $request, Location $location, TrainingSessionRepository $sessions, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'location-delete-'.$location->getId());
        $uses = $sessions->findActiveByLocation($location);
        if ($uses !== []) {
            $days = [1 => 'Montag', 2 => 'Dienstag', 3 => 'Mittwoch', 4 => 'Donnerstag', 5 => 'Freitag', 6 => 'Samstag', 7 => 'Sonntag'];
            $where = array_map(static fn (TrainingSession $s): string => sprintf('%s – %s, %s–%s Uhr', $s->getCourse()->getName(), $days[$s->getWeekday()], $s->getStartsAt()->format('H:i'), $s->getEndsAt()->format('H:i')), $uses);
            $this->addFlash('error', sprintf('Die Location „%s“ ist noch aktiv belegt und wurde nicht gelöscht: %s.', $location->getName(), implode('; ', $where)));

            return $this->redirectToRoute('editor_locations');
        }
        $location->setActive(false);
        $em->flush();
        $this->addFlash('success', 'Die Location wurde deaktiviert.');

        return $this->redirectToRoute('editor_locations');
    }

    #[Route('/courses/{id}/delete', name: 'course', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function course(Request $request, Course $course, TrainingSessionRepository $sessions, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'course-delete-'.$course->getId());
        $uses = $sessions->findActiveByCourse($course);
        if ($uses !== []) {
            $this->addFlash('error', sprintf('Der Kurs „%s“ besitzt noch %d aktive Trainingszeit(en) und wurde deshalb nicht deaktiviert. Bitte blenden Sie zuerst diese Trainingszeiten aus.', $course->getName(), count($uses)));
            return $this->redirectToRoute('editor_courses');
        }
        $course->setActive(false);
        $em->flush();
        $this->addFlash('success', 'Der Kurs wurde deaktiviert.');

        return $this->redirectToRoute('editor_courses');
    }

    #[Route('/users/{id}/delete', name: 'user', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function user(Request $request, User $user, EntityManagerInterface $em): Response
    {
        $this->csrf($request, 'user-delete-'.$user->getId());
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Das eigene Administratorkonto kann nicht deaktiviert werden.');
        } else {
            $user->setActive(false);
            $em->flush();
            $this->addFlash('success', 'Der Benutzer wurde deaktiviert.');
        }

        return $this->redirectToRoute('editor_users');
    }

    private function requirePermission(string $permission): User
    {
        $user = $this->getUser();
        if (!$user instanceof User || !$user->hasPermission($permission)) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }

    private function isAdmin(User $user): bool { return in_array('ROLE_ADMIN', $user->getRoles(), true); }

    private function csrf(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }
    }
}
