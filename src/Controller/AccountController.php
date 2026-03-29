<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        // Récupération de l'utilisateur connecté
        $user = $this->getUser();
        
        // Récupération des commandes de l'utilisateur
        $orders = $user->getOrders();
        
        // Conversion en tableau pour tri
        $ordersArray = $orders->toArray();
        
        // Tri par date décroissante (plus récentes en premier)
        usort($ordersArray, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        return $this->render('account/index.html.twig', [
            'orders' => $ordersArray,
        ]);
    }
}
