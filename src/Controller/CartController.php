<?php

namespace App\Controller;

use App\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(Product $product, Request $request): Response
    {
        $quantity = (int) $request->request->get('quantity', 1);

        // Récupération du panier en session
        $cart = $request->getSession()->get('cart', []);

        if ($quantity > 0) {
            // Ajouter ou mettre à jour
            $cart[$product->getId()] = $quantity;
        } else {
            // Quantité = 0 → suppression
            unset($cart[$product->getId()]);
        }

        // Sauvegarde
        $request->getSession()->set('cart', $cart);

        return $this->redirectToRoute('app_cart');
    }
    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request): Response
    {
        $cart = $request->getSession()->get('cart', []);
        return $this->render('cart/index.html.twig', [
            'cart' => $cart,
        ]);
    }

    #[Route('/cart/clear', name: 'app_cart_clear')]
    public function clear(Request $request): Response
    {
        $request->getSession()->remove('cart');

        return $this->redirectToRoute('app_cart');
    }
}
