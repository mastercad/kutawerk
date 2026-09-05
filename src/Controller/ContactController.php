<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/contact', name: 'contact_')]
final class ContactController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $users): Response
    {
        $contacts = [];
        foreach ($users->findContactPeople() as $person) {
            foreach ($person->getDepartments() as $department) $contacts[$department->getSlug()][] = $person;
        }
        return $this->render('pages/kontakt.html.twig', ['contacts' => $contacts]);
    }

    #[Route('/person/{id}', name: 'person', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function person(Request $request, User $person, MailerInterface $mailer, #[Autowire('%env(MAILER_FROM)%')] string $from): Response
    {
        $recipient = $person->getEmail();
        if (!$person->isActive() || !$person->isContactPerson() || $recipient === null) throw $this->createNotFoundException();
        $values = ['name' => '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];
        if ($request->isMethod('POST')) {
            foreach (array_keys($values) as $key) $values[$key] = trim((string) $request->request->get($key));
            if (!$this->isCsrfTokenValid('contact-'.$person->getId(), (string) $request->request->get('_token'))) throw $this->createAccessDeniedException();
            if (trim((string) $request->request->get('website')) !== '') {
                $this->addFlash('contact_success', 'Deine Nachricht wurde an '.$person->getDisplayName().' übermittelt.');
                return $this->redirectToRoute('contact_person', ['id' => $person->getId()]);
            }
            if ($values['name'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL) || $values['phone'] === '' || $values['message'] === '') {
                $this->addFlash('contact_error', 'Bitte fülle Name, eine gültige E-Mail-Adresse, Telefon und die Nachricht aus.');
            } else {
                $mailer->send((new TemplatedEmail())->from($from)->to($recipient)->replyTo($values['email'])->subject($values['subject'] !== '' ? $values['subject'] : 'Kontaktanfrage über kutawerk.de')->htmlTemplate('emails/contact.html.twig')->context(['senderName' => $values['name'], 'senderEmail' => $values['email'], 'senderPhone' => $values['phone'], 'message' => $values['message'], 'person' => $person]));
                $this->addFlash('contact_success', 'Deine Nachricht wurde an '.$person->getDisplayName().' übermittelt.');
                return $this->redirectToRoute('contact_person', ['id' => $person->getId()]);
            }
        }
        return $this->render('pages/contact-person.html.twig', ['person' => $person, 'values' => $values]);
    }

    #[Route('/board', name: 'board', methods: ['GET'])]
    public function board(): Response { return $this->render('pages/kontakt__vorstand.html.twig'); }

    #[Route('/dance', name: 'dance', methods: ['GET'])]
    public function dance(UserRepository $users): Response { return $this->section($users, 'dance', 'pages/kontakt__tanzsparte.html.twig'); }

    #[Route('/dance/ticket-presale', name: 'tickets', methods: ['GET'])]
    public function tickets(): Response { return $this->render('pages/kontakt__tanzsparte__karten-vorverkauf.html.twig'); }

    #[Route('/culture', name: 'culture', methods: ['GET'])]
    public function culture(UserRepository $users): Response { return $this->section($users, 'culture', 'pages/kontakt__kultursparte.html.twig'); }

    #[Route('/technology', name: 'technology', methods: ['GET'])]
    public function technology(UserRepository $users): Response { return $this->section($users, 'technology', 'pages/kontakt__techniksparte.html.twig'); }

    #[Route('/kuta-lounge', name: 'lounge', methods: ['GET'])]
    public function lounge(UserRepository $users): Response { return $this->section($users, 'kuta-lounge', 'pages/kontakt__kuta-lounge.html.twig'); }
    private function section(UserRepository $users, string $slug, string $template): Response
    {
        $people = [];
        foreach ($users->findContactPeople() as $person) {
            foreach ($person->getDepartments() as $department) {
                if ($department->getSlug() === $slug) {
                    $people[] = $person;
                    break;
                }
            }
        }
        return $this->render($template, ['people' => $people]);
    }
}
