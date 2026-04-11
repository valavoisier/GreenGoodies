<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur responsable de l’affichage des pages produit.
 *
 * Cette classe gère l’affichage du détail d’un produit individuel.
 * Symfony injecte automatiquement l’entité Product correspondante
 * grâce au ParamConverter, à partir de l’identifiant présent dans l’URL.
 *
 * Méthode:
 * - show() : affiche la fiche détaillée d’un produit.
 */
final class ProductController extends AbstractController
{
    /**
     * Affiche la page détaillée d’un produit.
     *
     * Fonctionnement :
     * - L’URL contient un paramètre {id}
     * - Symfony détecte que la méthode attend un objet Product
     * - Grâce au ParamConverter automatique, Symfony appelle
     *   ProductRepository::find($id) en interne
     * - Si le produit existe, il est injecté directement dans $product
     * - La vue Twig reçoit l’objet complet pour l’affichage
     */
     #[Route('/products/{id}', name: 'app_product_show', requirements: ['id' => '\d+'])]//(regex de 0 à 9)
    public function show(Product $product): Response
    {
        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
