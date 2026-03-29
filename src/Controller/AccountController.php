<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;

final class AccountController extends AbstractController
{
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
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
    
    #[Route('/account/api-toggle', name: 'app_account_api_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleApi(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('api_toggle', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setApiAccess(!$user->isApiAccess());
        $em->flush();

        return $this->redirectToRoute('app_account');
    }

    #[Route('/account/delete', name: 'app_account_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        // Vérification du token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('account_delete', $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        
        // Récupération de l'utilisateur connecté
        /** @var User $user */  //évite pb reconnaissance getOrders() par l'IDE
        $user = $this->getUser();
        
        // Supprimer toutes les commandes de l'utilisateur
        foreach ($user->getOrders() as $order) {
            $em->remove($order);
        }
        
        // Supprimer l'utilisateur
        $em->remove($user);
        $em->flush();
        
        // Déconnecter l'utilisateur
        $security->logout(false);
        
        return $this->redirectToRoute('app_home');
    }
}
