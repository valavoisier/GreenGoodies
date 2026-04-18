<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class ApiController extends AbstractController
{
    /**
     * Point d'entrée de l'authentification API.
     *
     * Cette méthode ne s'exécute jamais — elle existe uniquement pour que le
     * routeur Symfony puisse résoudre /api/login. Le firewall `json_login`
     * intercepte la requête avant d'atteindre ce contrôleur.
     *
     * @see config/packages/security.yaml  (json_login / success_handler)
     * @see App\Security\ApiAuthenticationSuccessHandler
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): never
    {
        throw new \LogicException('Intercepté par le firewall json_login.');
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
