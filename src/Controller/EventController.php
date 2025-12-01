<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Repository\InscriptionRepository;
use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/', name: 'app_events')]
    public function index(Request $request, EventService $eventService): Response
    {
        $startDate = $request->query->get('start_date') 
            ? new \DateTime($request->query->get('start_date')) 
            : null;
        $endDate = $request->query->get('end_date') 
            ? new \DateTime($request->query->get('end_date')) 
            : null;

        if ($startDate || $endDate) {
            $events = $eventService->getEventsByDateRange($startDate, $endDate);
        } else {
            $events = $eventService->getUpcomingEvents();
        }

        return $this->render('event/index.html.twig', [
            'events' => $events,
            'start_date' => $request->query->get('start_date'),
            'end_date' => $request->query->get('end_date'),
        ]);
    }

    #[Route('/mes', name: 'app_my_events')]
    public function myEvents(EventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        $events = $eventRepository->findBy(['createdBy' => $user]);

        return $this->render('event/my_events.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/new', name: 'app_event_new')]
    public function new(Request $request, EventService $eventService): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            
            $event->setCreatedBy($this->getUser());
            $eventService->createEvent($event, $file);

            return $this->redirectToRoute('app_my_events');
        }

        return $this->render('event/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/inscriptions', name: 'app_my_inscriptions')]
    public function mesInscriptions(InscriptionRepository $inscriptionRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $inscriptions = $inscriptionRepository->findBy(['user' => $user], ['inscritAt' => 'DESC']);

        return $this->render('event/my_inscriptions.html.twig', [
            'inscriptions' => $inscriptions,
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', requirements: ['id' => '\d+'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Event $event, EventService $eventService): Response
    {
        // Vérifier que l'utilisateur est le créateur de l'événement
        if ($event->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier cet événement.');
            return $this->redirectToRoute('app_my_events');
        }

        $form = $this->createForm(EventType::class, $event);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            
            $eventService->updateEvent($event, $file);

            $this->addFlash('success', 'L\'événement a été modifié avec succès.');
            return $this->redirectToRoute('app_my_events');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Event $event, EventService $eventService): Response
    {
        // Vérifier que l'utilisateur est le créateur de l'événement
        if ($event->getCreatedBy() !== $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer cet événement.');
            return $this->redirectToRoute('app_my_events');
        }

        // Vérifier le token CSRF
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $eventService->deleteEvent($event);
            $this->addFlash('success', 'L\'événement a été supprimé avec succès.');
        }

        return $this->redirectToRoute('app_my_events');
    }

    #[Route('/{id}/inscrire', name: 'app_event_inscrire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function inscrire(Request $request, Event $event, EventService $eventService): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté pour vous inscrire à un événement.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('inscrire'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        try {
            $eventService->inscribeUser($event, $user);
            $this->addFlash('success', 'Vous êtes maintenant inscrit à cet événement !');
        } catch (\Exception $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/desinscrire', name: 'app_event_desinscrire', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function desinscrire(Request $request, Event $event, EventService $eventService): Response
    {
        // Vérifier que l'utilisateur est connecté
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('error', 'Vous devez être connecté.');
            return $this->redirectToRoute('app_login');
        }

        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('desinscrire'.$event->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        try {
            $eventService->unsubscribeUser($event, $user);
            $this->addFlash('success', 'Vous êtes maintenant désinscrit de cet événement.');
        } catch (\Exception $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }
}
