# API RESTful Symfony pour Gestion de Jeux Vidéo

Ce projet est une API RESTful complète développée avec Symfony pour la gestion d'une base de données de jeux vidéo, incluant les catégories et éditeurs associés. L'API implémente un système d'authentification JWT pour sécuriser les opérations sensibles.

## Fonctionnalités

- **Gestion complète des entités** : Jeux vidéo, catégories, éditeurs et utilisateurs
- **CRUD pour chaque entité** avec validation des données
- **Authentification JWT** avec gestion des rôles (ROLE_USER, ROLE_ADMIN)
- **Pagination** des résultats pour optimiser les performances
- **Mise en cache** avec système d'invalidation
- **Documentation API** avec Swagger/OpenAPI
- **Gestion centralisée des exceptions**
- **Jeu de données initial** via les fixtures

## Technologies utilisées

- **Symfony 7**
- **Doctrine ORM**
- **JWT Authentication** (LexikJWTAuthenticationBundle)
- **API Documentation** (NelmioApiDocBundle)
- **MySQL 8.4**

## Structure du projet

Le projet est organisé selon l'architecture standard de Symfony :

```
src/
├── Controller/            # Contrôleurs pour chaque entité
├── Entity/                # Définition des entités et relations
├── Repository/            # Repositories pour l'accès aux données
└── DataFixtures/          # Données de test
```

## Installation

### Prérequis

- PHP 8.2 ou supérieur
- Composer
- MySQL 8.4
- Symfony CLI (recommandé)

### Étapes d'installation

1. Cloner le dépôt :
```bash
git clone <url-du-dépôt>
cd videogame-api
```

2. Installer les dépendances :
```bash
composer install
```

3. Configurer la base de données dans le fichier `.env` :
```
DATABASE_URL="mysql://root:@127.0.0.1:3306/api?serverVersion=8.4"
```

4. Générer les clés JWT :
```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

5. Créer la base de données et exécuter les migrations :
```bash
symfony console doctrine:database:create
symfony console doctrine:schema:create
```

6. Charger les fixtures (données de test) :
```bash
symfony console doctrine:fixtures:load
```

7. Lancer le serveur de développement :
```bash
symfony server:start
```

## Entités et relations

Le projet gère quatre entités principales avec les relations suivantes :

- **VideoGame** : entité principale (title, releaseDate, description)
    - Relation ManyToOne avec Category
    - Relation ManyToOne avec Editor

- **Category** : catégories de jeux (name)
    - Relation OneToMany avec VideoGame

- **Editor** : éditeurs de jeux (name, country)
    - Relation OneToMany avec VideoGame

- **User** : utilisateurs de l'API (email, roles, password)
    - Utilisé pour l'authentification et les autorisations

## Utilisation de l'API

### Authentification

L'authentification se fait via JWT. Pour obtenir un token :

```http
POST /api/login_check
Content-Type: application/json

{
  "email": "admin@example.com",
  "password": "password"
}
```

Réponse :
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGci..."
}
```

Utilisez ce token dans l'en-tête Authorization pour les requêtes authentifiées :
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGci...
```

### Utilisateurs par défaut

- **Admin** :
    - Email : admin@example.com
    - Mot de passe : password
    - Rôles : ROLE_ADMIN, ROLE_USER

- **Utilisateur standard** :
    - Email : user@example.com
    - Mot de passe : password
    - Rôles : ROLE_USER

### Points d'accès (endpoints)

#### Jeux vidéo
- GET `/api/video-games` : Liste des jeux (public)
- GET `/api/video-games/{id}` : Détails d'un jeu (public)
- POST `/api/video-games` : Créer un jeu (ROLE_ADMIN)
- PUT `/api/video-games/{id}` : Modifier un jeu (ROLE_ADMIN)
- DELETE `/api/video-games/{id}` : Supprimer un jeu (ROLE_ADMIN)

#### Catégories
- GET `/api/categories` : Liste des catégories (public)
- GET `/api/categories/{id}` : Détails d'une catégorie (public)
- POST `/api/categories` : Créer une catégorie (ROLE_ADMIN)
- PUT `/api/categories/{id}` : Modifier une catégorie (ROLE_ADMIN)
- DELETE `/api/categories/{id}` : Supprimer une catégorie (ROLE_ADMIN)

#### Éditeurs
- GET `/api/editors` : Liste des éditeurs (public)
- GET `/api/editors/{id}` : Détails d'un éditeur (public)
- POST `/api/editors` : Créer un éditeur (ROLE_ADMIN)
- PUT `/api/editors/{id}` : Modifier un éditeur (ROLE_ADMIN)
- DELETE `/api/editors/{id}` : Supprimer un éditeur (ROLE_ADMIN)

#### Utilisateurs
- GET `/api/users` : Liste des utilisateurs (ROLE_ADMIN)
- GET `/api/users/{id}` : Détails d'un utilisateur (ROLE_ADMIN)
- POST `/api/users` : Créer un utilisateur (ROLE_ADMIN)
- PUT `/api/users/{id}` : Modifier un utilisateur (ROLE_ADMIN)
- DELETE `/api/users/{id}` : Supprimer un utilisateur (ROLE_ADMIN)

### Pagination

Tous les endpoints de liste supportent la pagination :

```
GET /api/video-games?page=1&limit=10
```

## Documentation de l'API

La documentation complète de l'API est disponible via Swagger/OpenAPI :

```
http://localhost:8000/api/docs
```

Cette interface permet de :
- Explorer tous les endpoints disponibles
- Voir les modèles de données
- Tester les requêtes directement depuis l'interface
- S'authentifier avec un token JWT

## Migrations et fixtures

### Migrations

Les migrations Doctrine permettent de gérer les modifications de schéma de base de données de manière incrémentielle. Le projet utilise les commandes standards de Symfony pour gérer les migrations :

```bash
# Créer une nouvelle migration
symfony console make:migration

# Exécuter les migrations
symfony console doctrine:migrations:migrate
```

### Fixtures

Les fixtures sont utilisées pour générer un jeu de données initial, facilitant le développement et les tests. Les fixtures incluent :

- Deux utilisateurs (admin et utilisateur standard)
- Sept catégories de jeux (Action, Adventure, RPG, etc.)
- Sept éditeurs avec leurs pays d'origine
- Dix jeux vidéo avec leurs relations

Pour recharger les fixtures :

```bash
symfony console doctrine:fixtures:load
```

## Sécurité

L'API implémente les pratiques de sécurité suivantes :

- **Authentification JWT** pour les sessions sans état
- **Contrôle d'accès basé sur les rôles**
- **Validation des données entrantes**
- **Hachage sécurisé des mots de passe**
- **Réponses d'erreur standardisées**

## Fonctionnalités avancées

### Gestion du cache

L'API utilise un système de cache avec invalidation automatique pour améliorer les performances :

- Les résultats des requêtes GET sont mis en cache
- Le cache est invalidé automatiquement lors des modifications (POST, PUT, DELETE)
- La durée de vie du cache est configurable

### Gestion des exceptions

Un gestionnaire d'exceptions centralisé transforme toutes les erreurs en réponses JSON standardisées avec les codes HTTP appropriés.

---

Développé dans le cadre d'un projet de cours.