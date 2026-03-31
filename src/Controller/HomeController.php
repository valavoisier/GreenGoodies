<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur responsable de l’affichage de la page d’accueil.
 *
 * Cette classe gère la récupération et l’affichage des produits visibles
 * sur la page d’accueil du site. Elle utilise le ProductRepository pour accéder aux données
 *
 * Méthode principale :
 * - index() : récupère tous les produits et affiche la page d’accueil.
 */
final class HomeController extends AbstractController
{
    /**
     * Affiche la page d’accueil avec la liste des produits.
     *
     * Fonctionnement :
     * - Récupère tous les produits via ProductRepository
     * - Transmet les données à la vue Twig
     * - Affiche la page d’accueil
     */
    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        // Récupération de tous les produits en base.
        $products = $productRepository->findAll();

        // Transmission des produits à la vue Twig pour affichage.
        return $this->render('home/index.html.twig', [
        'products' => $products,
    ]);
    }
}
