<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CartController extends AbstractController
{
    #[Route('/cart/add/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
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
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        $cart = $request->getSession()->get('cart', []);
        
        // Récupération des produits avec leurs quantités
        $cartItems = [];
        $total = 0;
        
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $subtotal = $product->getPrice() * $quantity;
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
                $total += $subtotal;
            }
        }
        
        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    #[Route('/cart/clear', name: 'app_cart_clear')]
    #[IsGranted('ROLE_USER')]
    public function clear(Request $request): Response
    {
        $request->getSession()->remove('cart');

        return $this->redirectToRoute('app_cart');
    }
    
    #[Route('/cart/validate', name: 'app_cart_validate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function validate(Request $request, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cart_validate', $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        
        $cart = $request->getSession()->get('cart', []);
        
        // Vérifier que le panier n'est pas vide
        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }
        
        // Calculer le total
        $total = 0;
        foreach ($cart as $productId => $quantity) {
            $product = $productRepository->find($productId);
            if ($product) {
                $total += $product->getPrice() * $quantity;
            }
        }
        
        // Créer la commande
        $order = new Order();
        $order->setUser($this->getUser());
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setTotalPrice((string) $total);
        
        // Sauvegarder en base
        $em->persist($order);
        $em->flush();
        
        // Vider le panier
        $request->getSession()->remove('cart');
        
        // Message de confirmation
        $this->addFlash('success', 'Votre commande a été validée avec succès !');
        
        return $this->redirectToRoute('app_cart');
    }
    
}
