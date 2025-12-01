<?php

namespace App\Controller;

use App\Service\EventService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
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

        return $this->render('home/index.html.twig', [
            'events' => $events,
            'start_date' => $request->query->get('start_date'),
            'end_date' => $request->query->get('end_date'),
        ]);
    }
}
