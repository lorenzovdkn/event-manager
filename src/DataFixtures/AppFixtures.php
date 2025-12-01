<?php

namespace App\DataFixtures;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer 3 utilisateurs
        $users = [];
        for ($i = 1; $i <= 3; $i++) {
            $user = new User();
            $user->setName($faker->name());
            $user->setEmail("user{$i}@example.com");
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, 'password123')
            );

            $manager->persist($user);
            $users[] = $user;
        }

        // Catégories d'événements pour varier les contenus
        $eventCategories = [
            'Technologie' => [
                'Conférence',
                'Workshop',
                'Hackathon',
                'Meetup',
            ],
            'Culture' => [
                'Concert',
                'Exposition',
                'Théâtre',
                'Festival',
            ],
            'Sport' => [
                'Match',
                'Tournoi',
                'Course',
                'Compétition',
            ],
            'Business' => [
                'Séminaire',
                'Networking',
                'Présentation',
                'Forum',
            ],
        ];

        $lieux = [
            'Centre de Conférences de Paris',
            'Palais des Congrès',
            'Zénith de Lyon',
            'Stade de France',
            'Parc des Expositions',
            'Salle Pleyel',
            'Grand Palais',
            'Musée du Louvre',
            'Bibliothèque Nationale',
            'Campus Universitaire',
        ];

        // Créer 15 événements
        for ($i = 0; $i < 15; $i++) {
            $event = new Event();

            // Sélectionner une catégorie et un type d'événement
            $category = $faker->randomElement(array_keys($eventCategories));
            $eventType = $faker->randomElement($eventCategories[$category]);

            $event->setTitre($eventType . ' ' . $category . ' ' . $faker->year());
            
            // Description réaliste
            $event->setDescription(
                $faker->realText(300) . "\n\n" .
                "Cet événement vous permettra de découvrir les dernières tendances et innovations. " .
                "Ne manquez pas cette opportunité unique de rencontrer des experts du domaine."
            );

            // Date de début entre maintenant et 6 mois
            $dateDebut = $faker->dateTimeBetween('now', '+6 months');
            $event->setDate(\DateTime::createFromInterface($dateDebut));

            // Date de fin entre 2 heures et 3 jours après le début
            $dateFin = clone $dateDebut;
            $dateFin->modify('+' . $faker->numberBetween(2, 72) . ' hours');
            $event->setDateFin(\DateTime::createFromInterface($dateFin));

            $event->setLieu($faker->randomElement($lieux));
            
            // Assigner un créateur aléatoire
            $event->setCreatedBy($faker->randomElement($users));
            $event->setCreatedAt(new \DateTimeImmutable());

            $manager->persist($event);
        }

        $manager->flush();

        echo "\n✅ Fixtures chargées avec succès!\n";
        echo "📧 3 utilisateurs créés (user1@example.com, user2@example.com, user3@example.com)\n";
        echo "🔑 Mot de passe pour tous: password123\n";
        echo "📅 15 événements créés avec dates, heures et lieux\n\n";
    }
}
