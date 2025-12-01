# EventManager - Application de Gestion d'Événements

Application web Symfony 7.3 pour la création et la gestion d'événements avec système d'inscription.

## Installation

```bash
git clone https://github.com/lorenzovdkn/event-manager.git
cd event-manager
docker compose up -d
docker compose exec php composer install
docker compose exec php php bin/console doctrine:database:create
docker compose exec php php bin/console doctrine:migrations:migrate -n
docker compose exec php php bin/console doctrine:fixtures:load -n
```

Application disponible sur http://localhost:8080

## Comptes de test

- **Email** : user1@example.com / user2@example.com / user3@example.com
- **Mot de passe** : password123

## Fonctionnalités

### Visiteurs
- Consultation des événements à venir
- Filtre par intervalle de dates
- Création de compte

### Utilisateurs authentifiés
- Création d'événements (titre, description, dates, lieu, image)
- Modification/suppression de ses événements
- Inscription/désinscription aux événements
- Consultation de ses inscriptions

## Technologies

- Symfony 7.3
- PHP 8.4-fpm
- PostgreSQL 16
- Bootstrap 5.3
- Docker & Docker Compose
- Doctrine ORM
- FakerPHP

La logique métier est centralisée dans le service `EventService` pour faciliter la maintenance et les tests.

## Sécurité

- Validation stricte des mots de passe (8+ caractères, majuscule, chiffre, caractère spécial)
- Protection CSRF sur tous les formulaires
- Contrôle des droits (modification/suppression réservée au créateur)
- Contrainte d'unicité sur les inscriptions

## Commandes utiles

```bash
# Redémarrer PHP
docker compose restart php

# Voir les logs
docker compose logs -f php

# Recharger les fixtures
docker compose exec php php bin/console doctrine:fixtures:load -n

# Vider le cache
docker compose exec php php bin/console cache:clear
```

## Configuration

Le fichier `.env` est configuré en mode production par défaut.


## Contraintes respectées

- Dernière version Symfony (7.3)
- Fixtures générées avec Faker
- Interface responsive avec Bootstrap
- Authentification via le composant Security
- Logique métier dans des services
- Mode production configuré
- Versioning Git

## Auteur

Lorenzo VANDENKOORNHUYSE
