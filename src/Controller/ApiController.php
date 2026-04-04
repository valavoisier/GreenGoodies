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
use Symfony\Component\Serializer\SerializerInterface;

final class ApiController extends AbstractController
{
    /**
     * Authentifie un utilisateur et génère un token JWT.
     *
     * Méthode : POST  
     * URL     : /api/login  
     * Accès   : Public (compte requis + accès API activé)
     *
     * Processus :
     * - Lecture du JSON envoyé par le client
     * - Vérification des identifiants (email + mot de passe)
     * - Vérification que l’accès API est activé pour cet utilisateur
     * - Génération d’un token JWT via LexikJWTAuthenticationBundle
     *
     * Corps attendu (JSON) :
     * {
     *   "username": "email@example.com",
     *   "password": "monMotDePasse"
     * }
     *
     * Codes de réponse :
     * - 200 : Token généré
     * {
     *   "token": "eyJhbGciOi..."
     * }
     * - 401 : Identifiants manquants ou incorrects
     * - 403 : Accès API désactivé
     * 
     * Notes :
     * - Le token JWT doit être envoyé dans l’en-tête :
     *   Authorization: Bearer <token>
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
     * Retourne la liste des produits du catalogue.
     *
     * Méthode : GET  
     * URL     : /api/products  
     * Accès   : Protégé (JWT obligatoire)
     *
     * En-tête requis :
     * Authorization: Bearer <token>
     *     
     * Codes de réponse :
     * - 200 : Succès
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
     * - 401 : Token manquant ou invalide
     */
    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function products(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        // Récupération de tous les produits en base.
        // findAll() renvoie un tableau d’entités Product.
        $products = $productRepository->findAll();
        // Sérialisation des entités Product en JSON via le Serializer Symfony.
        // Le groupe 'product:read' détermine les propriétés exposées (définies dans l'entité).
        $json = $serializer->serialize($products, 'json', ['groups' => ['product:read']]);

        // Retour de la réponse JSON avec le contenu déjà sérialisé.
        // Le 4e paramètre (true) indique que $json est déjà encodé → pas de double encodage.
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
