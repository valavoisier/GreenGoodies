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

/**
 * Contrôleur responsable de la gestion du panier utilisateur.
 *
 * Cette classe regroupe toutes les actions liées au panier :
 * - Ajout d’un produit au panier
 * - Affichage du panier avec calcul des sous‑totaux et du total
 * - Vidage complet du panier
 * - Validation du panier et création d’une commande
 *
 * Le panier est stocké en session sous forme de tableau associatif :
 * [
 *     productId => quantity,
 *     ...
 * ]
 *
 * Méthodes principales :
 * - add()       : ajoute ou met à jour un produit dans le panier
 * - index()     : affiche le contenu du panier et calcule le total
 * - clear()     : vide complètement le panier
 * - validate()  : valide le panier et crée une commande en base
 */
final class CartController extends AbstractController
{
    /**
     * Ajoute un produit au panier ou met à jour sa quantité.
     *
     * Fonctionnement :
     * - Récupère la quantité envoyée par le formulaire (par défaut 1)
     * - Récupère le panier en session
     * - Ajoute ou met à jour la quantité du produit
     * - Si quantité = 0 → suppression du produit du panier
     * - Sauvegarde du panier en session
     * - Redirection vers la page du panier
     */
    #[Route('/cart/add/{id}', name: 'app_cart_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(Product $product, Request $request): Response
    {
        // Récupération de la quantité envoyée par le formulaire (1 par défaut).
        $quantity = (int) $request->request->get('quantity', 1);

        // Récupération du panier stocké en session
        // Structure : [ productId => quantity ]
        // clé 'cart' : nom choisi pour stocker le panier en session
        // get('cart', []) : si la clé 'cart' n’existe pas, retourne un tableau vide par défaut
        $cart = $request->getSession()->get('cart', []);

        if ($quantity > 0) {
            // Ajout ou mise à jour de la quantité du produit.
            $cart[$product->getId()] = $quantity;
        } else {
            // Quantité = 0 → suppression du produit du panier
            unset($cart[$product->getId()]);
        }

        // Sauvegarde du panier mis à jour en session.
        $request->getSession()->set('cart', $cart);

        // Redirection vers la page du panier.
        return $this->redirectToRoute('app_cart');
    }
    
    /**
     * Affiche le contenu du panier.
     *
     * Fonctionnement :
     * - Récupère le panier en session
     * - Pour chaque produit : récupère l’entité Product correspondante
     * - Calcule le sous‑total (prix × quantité)
     * - Calcule le total général du panier
     * - Envoie les données à la vue Twig
     */
    #[Route('/cart', name: 'app_cart')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request, ProductRepository $productRepository): Response
    {
        // Récupération du panier stocké en session.
        // Structure : [ productId => quantity ]
        $cart = $request->getSession()->get('cart', []);
        
        // Préparation des structures nécessaires pour reconstruire le panier.
        // $cartItems contiendra les lignes complètes (produit, quantité, sous‑total)
        // afin d’être directement utilisables dans la vue Twig.
        // $total initialisé à 0 servira d’accumulateur pour calculer le montant global du panier.
        $cartItems = [];
        $total = 0;
        
        // Génération des lignes du panier à partir des données stockées en session.
        foreach ($cart as $productId => $quantity) {
            // Récupération du produit correspondant.
            $product = $productRepository->find($productId);
            // if ($product) protège contre un produit supprimé entre temps 
            if ($product) {
                // Calcul du sous‑total pour ce produit.
                $subtotal = $product->getPrice() * $quantity;
                // Ajout d’une ligne complète au tableau d’affichage.
                $cartItems[] = [
                    'product' => $product,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                ];
                // Ajout au total général.
                $total += $subtotal;
            }
        }
        
        // Affichage de la page du panier.
        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    /**
     * Vide complètement le panier.
     *
     * Fonctionnement :
     * - Supprime la clé "cart" de la session
     * - Redirige vers la page du panier
     */
    #[Route('/cart/clear', name: 'app_cart_clear')]
    #[IsGranted('ROLE_USER')]
    public function clear(Request $request): Response
    {
        // Suppression du panier en session.
        $request->getSession()->remove('cart');

        // Redirection vers la page du panier.
        return $this->redirectToRoute('app_cart');
    }

    /**
     * Valide le panier et crée une commande.
     *
     * Fonctionnement :
     * - Vérifie le token CSRF
     * - Récupère le panier en session
     * - Vérifie que le panier n’est pas vide
     * - Calcule le total de la commande
     * - Crée une entité Order associée à l’utilisateur
     * - Persiste la commande en base
     * - Vide le panier
     * - Affiche un message de confirmation
     */
    #[Route('/cart/validate', name: 'app_cart_validate', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function validate(Request $request, ProductRepository $productRepository, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF pour sécuriser la validation.
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('cart_validate', $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        
        // Récupération du panier en session.
        $cart = $request->getSession()->get('cart', []);
        
        // Vérification que le panier n'est pas vide
        if (empty($cart)) {
            return $this->redirectToRoute('app_cart');
        }
        
        // Calcul du total de la commande en parcourant les produits du panier.
        $total = 0;
        foreach ($cart as $productId => $quantity) {
            // Récupération de l’entité Product associée à l’identifiant stocké en session.
            $product = $productRepository->find($productId);
            if ($product) {
                // Ajout du sous‑total du produit au total général.
                $total += $product->getPrice() * $quantity;
            }
        }
        
        // Création de la commande
        $order = new Order();
        $order->setUser($this->getUser());
        // Date de création de la commande : on instancie un objet DateTimeImmutable,
        // qui représente l’instant présent et garantit une valeur non modifiable.
        $order->setCreatedAt(new \DateTimeImmutable());
        $order->setTotalPrice((string) $total);
        
        // Sauvegarde de la commande en base de données
        $em->persist($order);
        $em->flush();
        
        // Vider le panier après validation de la commande
        $request->getSession()->remove('cart');
        
        // Message de confirmation affiché à l’utilisateur 
        $this->addFlash('success', 'Votre commande a été validée avec succès !');
        
        // Redirection vers la page du panier 
        return $this->redirectToRoute('app_cart');
    }
    
}
