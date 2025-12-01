<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Inscription;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class EventService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private InscriptionRepository $inscriptionRepository,
        private SluggerInterface $slugger,
        private string $uploadsDirectory
    ) {
    }

    /**
     * Récupère tous les événements à venir triés par date
     */
    public function getUpcomingEvents(): array
    {
        return $this->eventRepository->createQueryBuilder('e')
            ->where('e.date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les événements dans un intervalle de dates
     */
    public function getEventsByDateRange(?\DateTime $startDate, ?\DateTime $endDate): array
    {
        $qb = $this->eventRepository->createQueryBuilder('e')
            ->where('e.date >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'ASC');

        if ($startDate) {
            $qb->andWhere('e.date >= :startDate')
                ->setParameter('startDate', $startDate);
        }

        if ($endDate) {
            $qb->andWhere('e.date <= :endDate')
                ->setParameter('endDate', $endDate);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Récupère les événements créés par un utilisateur
     */
    public function getUserCreatedEvents(User $user): array
    {
        return $this->eventRepository->findBy(['createdBy' => $user], ['createdAt' => 'DESC']);
    }

    /**
     * Crée un nouvel événement
     */
    public function createEvent(Event $event, User $creator, ?UploadedFile $imageFile = null): Event
    {
        if ($imageFile) {
            $imageName = $this->uploadImage($imageFile);
            $event->setImage($imageName);
        }

        $event->setCreatedBy($creator);
        $event->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $event;
    }

    /**
     * Met à jour un événement existant
     */
    public function updateEvent(Event $event, ?UploadedFile $imageFile = null): Event
    {
        if ($imageFile) {
            // Supprimer l'ancienne image
            if ($event->getImage()) {
                $this->deleteImage($event->getImage());
            }

            $imageName = $this->uploadImage($imageFile);
            $event->setImage($imageName);
        }

        $this->entityManager->flush();

        return $event;
    }

    /**
     * Supprime un événement et son image associée
     */
    public function deleteEvent(Event $event): void
    {
        if ($event->getImage()) {
            $this->deleteImage($event->getImage());
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();
    }

    /**
     * Vérifie si un utilisateur peut modifier/supprimer un événement
     */
    public function canManageEvent(Event $event, User $user): bool
    {
        return $event->getCreatedBy() === $user;
    }

    /**
     * Inscrit un utilisateur à un événement
     * 
     * @throws \RuntimeException si l'utilisateur est déjà inscrit
     */
    public function inscribeUser(Event $event, User $user): Inscription
    {
        // Vérifier si déjà inscrit
        $existingInscription = $this->inscriptionRepository->findOneBy([
            'user' => $user,
            'event' => $event
        ]);

        if ($existingInscription) {
            throw new \RuntimeException('Vous êtes déjà inscrit à cet événement.');
        }

        $inscription = new Inscription();
        $inscription->setUser($user);
        $inscription->setEvent($event);
        $inscription->setInscritAt(new \DateTimeImmutable());

        $this->entityManager->persist($inscription);
        $this->entityManager->flush();

        return $inscription;
    }

    /**
     * Désinscrit un utilisateur d'un événement
     * 
     * @throws \RuntimeException si l'utilisateur n'est pas inscrit
     */
    public function unsubscribeUser(Event $event, User $user): void
    {
        $inscription = $this->inscriptionRepository->findOneBy([
            'user' => $user,
            'event' => $event
        ]);

        if (!$inscription) {
            throw new \RuntimeException('Vous n\'êtes pas inscrit à cet événement.');
        }

        $this->entityManager->remove($inscription);
        $this->entityManager->flush();
    }

    /**
     * Récupère les inscriptions d'un utilisateur
     */
    public function getUserInscriptions(User $user): array
    {
        return $this->inscriptionRepository->findBy(
            ['user' => $user],
            ['inscritAt' => 'DESC']
        );
    }

    /**
     * Upload une image et retourne son nom
     */
    private function uploadImage(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        $file->move($this->uploadsDirectory, $fileName);

        return $fileName;
    }

    /**
     * Supprime une image du système de fichiers
     */
    private function deleteImage(string $imageName): void
    {
        $imagePath = $this->uploadsDirectory . '/' . $imageName;
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}
