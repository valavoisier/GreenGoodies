<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    /**
     * Cette méthode permet d’obtenir un token JWT pour accéder aux routes protégées de l’API.
     *
     * Méthode : POST  
     * URL     : /api/login  
     * Accès   : Public (mais nécessite un compte avec accès API activé)
     *
     * Processus :
     * - Lecture du JSON envoyé par le client
     * - Vérification des identifiants (email + mot de passe)
     * - Vérification que l’accès API est activé pour cet utilisateur
     * - Génération d’un token JWT via LexikJWTAuthenticationBundle
     *
     * Exemple de requête :
     * {
     *   "username": "email@example.com",
     *   "password": "monMotDePasse"
     * }
     *
     * Exemple de réponse :
     * {
     *   "token": "eyJhbGciOi..."
     * }
     *
     * Codes de réponse :
     * - 200 : Token généré
     * - 401 : Identifiants manquants ou incorrects
     * - 403 : Accès API désactivé
     */    
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        // Lecture et décodage du JSON envoyé par le client.
        // Le second paramètre (true) force un tableau associatif.
        $data = json_decode($request->getContent(), true);

        // Extraction sécurisée des identifiants envoyés dans la requête.
        // Le "?? null" évite une erreur si une clé est absente.
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        // Vérification de la présence des champs obligatoires.
        // Si l’un des deux est manquant → 401 Unauthorized.
        if (!$username || !$password) {
            return $this->json(['message' => 'Identifiants manquants.'], Response::HTTP_UNAUTHORIZED);
        }
        // Recherche de l’utilisateur correspondant à l’email fourni.
        // findOneBy() renvoie null si aucun utilisateur ne correspond.
        $user = $userRepository->findOneBy(['email' => $username]);

        // Vérification de l’existence de l’utilisateur et de la validité du mot de passe.
        // isPasswordValid() compare le mot de passe en clair avec le hash stocké.
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Identifiants incorrects.'], Response::HTTP_UNAUTHORIZED);
        }
        // Vérification que l’utilisateur a activé l’accès API dans son espace personnel.
        // Cela permet de désactiver l’accès API sans supprimer le compte.
        if (!$user->isApiAccess()) {
            return $this->json(['message' => 'Accès API non activé.'], Response::HTTP_FORBIDDEN);
        }
        // Génération du token JWT pour l’utilisateur authentifié.
        // create() sérialise l’utilisateur et génère un token signé.
        $token = $jwtManager->create($user);

        // Retour du token dans une réponse JSON standardisée.
        return $this->json(['token' => $token], Response::HTTP_OK);
    }

    /**
     * Cette méthode permet de récupérer la liste complète des produits.
     *
     * Méthode : GET  
     * URL     : /api/products  
     * Accès   : Protégé (JWT obligatoire)
     *
     * Le client doit fournir un token valide dans l’en-tête :
     * Authorization: Bearer <token>
     *
     * Exemple de réponse :
     * [
     *   {
     *     "id": 1,
     *     "name": "Produit A",
     *     "shortDescription": "...",
     *     "fullDescription": "...",
     *     "price": 19.99,
     *     "picture": "image.jpg"
     *   }
     * ]
     *
     * Codes de réponse :
     * - 200 : Succès
     * - 401 : Token manquant ou invalide
     */
    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function products(ProductRepository $productRepository): JsonResponse
    {
        // Récupération de tous les produits en base.
        // findAll() renvoie un tableau d’entités Product.
        $products = $productRepository->findAll();

        // Transformation des entités Product en tableaux scalaires.
        // Cela garantit une sérialisation propre et évite les proxies Doctrine.
        $data = array_map(fn($p) => [
            'id'               => $p->getId(),
            'name'             => $p->getName(),
            'shortDescription' => $p->getShortDescription(),
            'fullDescription'  => $p->getFullDescription(),
            'price'            => $p->getPrice(),
            'picture'          => $p->getPicture(),
        ], $products);
        
        // Retour de la liste des produits au format JSON.
        return $this->json($data, Response::HTTP_OK);
    }
}
