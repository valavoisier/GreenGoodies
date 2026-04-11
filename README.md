# GreenGoodies

Site e-commerce de produits écologiques et éthiques, développée avec **Symfony 7.4**.

---

## Sommaire

- [Présentation](#présentation)
- [Stack technique](#stack-technique)
- [Installation](#installation)
- [Structure du projet](#structure-du-projet)
- [Entités](#entités)
- [Routes & Contrôleurs](#routes--contrôleurs)
- [Sécurité & Authentification](#sécurité--authentification)
- [API REST](#api-rest)
- [Gestion du panier](#gestion-du-panier)
- [Espace personnel](#espace-personnel)
- [Fixtures](#fixtures)

---

## Présentation

GreenGoodies, boutique lyonnaisespécialisée dans la vente de produits biologiques, éthiques et écologiques, souhaite élargir sa cible commerciale en proposant une plateforme de vente en ligne et une API REST.
le site propose:
- Un catalogue de produits consultable par tous
- Un espace personnel pour les utilisateurs connectés (panier, commandes, compte)
- Une API REST sécurisée par JWT pour les partenaires commerciaux souhaitant intégrer les produits dans leurs propres applications.

---

## Stack technique

- **Symfony 7.4** / PHP 8.2
- **Doctrine ORM 3** + MariaDB 10.4
- **Twig 3** + Bootstrap 5.3 (CDN)
- **LexikJWTAuthenticationBundle** — authentification API
- **Symfony Serializer** — sérialisation des entités en JSON (groupes de sérialisation)
---

## Installation

### Prérequis

- PHP >= 8.2
- Composer
- MariaDB 10.4.32 (via XAMPP) 

### Étapes

```bash
# 1. Cloner le projet
git clone 
cd GreenGoodies

# 2. Installer les dépendances
composer install

# 3. Configurer l'environnement
cp .env .env.local
# Renseigner DATABASE_URL, JWT_SECRET_KEY, JWT_PUBLIC_KEY, JWT_PASSPHRASE

# 4. Générer les clés JWT
php bin/console lexik:jwt:generate-keypair

# 5. Créer la base de données "greengoodies" et exécuter les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Charger les produits de démonstration
php bin/console doctrine:fixtures:load

# 7. Lancer le serveur de développement
symfony server:start
```
---

## Structure du projet

```
src/
├── Controller/
│   ├── AccountController.php      # Espace personnel utilisateur
│   ├── ApiController.php          # Endpoints de l'API REST
│   ├── CartController.php         # Gestion du panier
│   ├── HomeController.php         # Page d'accueil
│   ├── ProductController.php      # Fiche produit
│   ├── RegistrationController.php # Inscription
│   └── SecurityController.php     # Connexion / Déconnexion
├── Entity/
│   ├── Order.php                  # Commande
│   ├── Product.php                # Produit
│   └── User.php                   # Utilisateur
├── EventListener/
│   └── ApiExceptionListener.php   # Réponses JSON sur erreurs API
├── Form/
│   └── RegistrationFormType.php   # Formulaire d'inscription
├── Repository/
│   ├── OrderRepository.php        # findByUserOrderedByDate() — tri SQL
│   ├── ProductRepository.php
│   └── UserRepository.php
└── DataFixtures/
    └── AppFixtures.php            # Données de démonstration
templates/
├── base.html.twig
├── account/index.html.twig        # espace personnel (commandes, API)
├── cart/index.html.twig           # affichage du panier
├── home/index.html.twig           # page d'accueil avec liste des produits
├── partials/nav.html.twig         # menu de navigation
├── product/show.html.twig         # fiche produit
├── registration/register.html.twig # formulaire d'inscription
└── security/login.html.twig       # formulaire de connexion
```

---

## Entités

**User** — email, prénom, nom, mot de passe haché, rôles, `apiAccess` (booléen activable depuis le compte). La relation `OneToMany` vers `Order` est configurée avec `cascade: ['remove']` et `orphanRemoval: true` — la suppression d'un `User` entraîne automatiquement la suppression de toutes ses commandes.

**Product** — nom, description courte (homepage), description longue (fiche produit), prix, image. Les propriétés exposées via l'API sont annotées `#[Groups(['product:read'])]` — seuls ces champs sont sérialisés et retournés par `/api/products`.

**Order** — date de création, prix total, relation vers `User` (`ManyToOne`, `nullable: false`). La table SQL est nommée `` `order` `` (mot réservé SQL, protégé par backticks dans l'annotation Doctrine).

---

---

## Routes & Contrôleurs

### Pages publiques — accessibles sans connexion :

- `GET /` — page d'accueil
- `GET /products/{id}` — fiche produit
- `GET|POST /login` — connexion
- `GET|POST /register` — inscription
- `GET /logout` — déconnexion

### Pages protégées (ROLE_USER requis)

- `GET /cart` — panier
- `POST /cart/add/{id}` — ajout produit
- `GET /cart/clear` — vider le panier
- `POST /cart/validate` — valider la commande
- `GET /account` — espace personnel
- `POST /account/api-toggle` — activer/désactiver l'API
- `POST /account/delete` — supprimer le compte

---

## Sécurité & Authentification

La sécurité est configurée dans `config/packages/security.yaml`.

### Firewalls

Deux firewalls distincts dans `security.yaml` :

- **`api`** — stateless, JWT, couvre toutes les routes `/api`
- **`main`** — form login avec session, couvre le reste du site

### Contrôle d'accès


- `/api/login`        PUBLIC_ACCESS   
- `/api/products`    ROLE_USER (JWT)
- `/login`           PUBLIC_ACCESS   
- `/register`        PUBLIC_ACCESS   
- `/cart`            ROLE_USER       
- `/account`         ROLE_USER       

### Protection CSRF

Les formulaires sensibles (panier, compte, suppression) sont protégés par des tokens CSRF vérifiés côté serveur.

### Hachage des mots de passe

L'algorithme `auto` est utilisé en production.

---

## API REST

L'API REST est accessible sous le préfixe `/api`. Elle utilise **JSON Web Tokens (JWT)** pour l'authentification, via le bundle `lexik/jwt-authentication-bundle`.

L'accès API doit être activé manuellement depuis `/account`.
---

### `POST /api/login` — Obtenir un token JWT

**Accès :** Public

**Corps de la requête (JSON) :**

```json
{
  "username": "email@example.com",
  "password": "monMotDePasse"
}
```

**Réponse en cas de succès (200) :**

```json
{
  "token": "eyJhbGciOiJSUzI1NiJ9..."
}
```

**Codes de réponse :**

Réponses : `200` + token JWT / `401` identifiants incorrects / `403` accès API non activé.


---

### `GET /api/products` — Lister les produits

**Accès :** Protégé — token JWT obligatoire

**En-tête requis :**

```
Authorization: Bearer <token>
```

**Sérialisation :** Les produits sont exposés via le groupe `product:read`, 
ce qui garantit que seules les propriétés prévues pour l’API sont retournées.

**Réponse en cas de succès (200) :**

```json
[
  {
    "id": 1,
    "name": "Kit d'hygiène recyclable",
    "shortDescription": "Pour une salle de bain éco-friendly",
    "fullDescription": "Description complète du produit...",
    "price": 12.99,
    "picture": "kit-hygiene-recyclable.webp"
  },
  ...
]
```

**Codes de réponse 401 si Token manquant ou invalide  :**
    
---

### Gestion des erreurs API

Le listener `ApiExceptionListener` intercepte toutes les exceptions déclenchées sur les routes `/api` et retourne une réponse JSON standardisée.

 - `NotFoundHttpException`          (404 / Ressource introuvable)         
 - `AccessDeniedHttpException`      (403 / Accès refusé)                  
 - `UnauthorizedHttpException`      (401 / Non authentifié)               
 - `MethodNotAllowedHttpException`  (405 / Méthode HTTP non autorisée)    
 - Autre `HttpException`            (Code HTTP de l'exception / Message de l'exception) 
 - Erreur serveur                   (500 / Erreur interne du serveur)     

**Format de la réponse d'erreur :**

```json
{
  "message": "Identifiants incorrects."
}
```

---

### Exemple d'utilisation complet (Postman)

**1. Obtenir un token JWT**

- Méthode : `POST`
- URL : `http://localhost:8000/api/login`
- Onglet **Body** → `raw` → `JSON`

```json
{
  "username": "user@example.com",
  "password": "monMotDePasse"
}
```

Copier la valeur du champ `token` dans la réponse.

---

**2. Consommer l'API avec le token**

- Méthode : `GET`
- URL : `http://localhost:8000/api/products`
- Onglet **Authorization** → Type : `Bearer Token` → coller le token dans le champ **Token**

---

## Gestion du panier

Le panier est stocké en session PHP sous la forme `[ productId => quantity ]`. 
À la validation, une entité `Order` est créée en base avec le total calculé, puis le panier est vidé.

---

## Espace personnel

L’espace personnel (`/account`) regroupe trois fonctionnalités :

**Tableau des commandes** — les commandes sont récupérées via `OrderRepository::findByUserOrderedByDate()`, une requête DQL qui filtre par utilisateur et trie par date décroissante côté SQL (`ORDER BY createdAt DESC`).

**Accès API** — le bouton active ou désactive le booléen `apiAccess` sur l’entité `User` (POST `account/api-toggle`, protégé par CSRF). Sans ce booléen à `true`, l’endpoint `/api/login` retourne `403 Forbidden` même avec des identifiants valides.

**Suppression de compte** — la suppression du `User` déclenche automatiquement la suppression des `Order` associés grâce à `cascade: ['remove']` + `orphanRemoval: true` définis sur la relation `User::$orders`. Après le `flush()`, `$security->logout(false)` nettoie la session.

---

## Fixtures

les 9 produits de la boutique sont chargés via :
```bash
php bin/console doctrine:fixtures:load
```

Les images correspondantes doivent être présentes dans `assets/images/products/`. 
Les comptes utilisateurs se créent via `/register`.
