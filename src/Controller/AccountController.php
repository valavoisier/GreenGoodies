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

/**
 * Contrôleur responsable de la gestion de l’espace personnel utilisateur.
 *
 * Cette classe regroupe toutes les actions liées au compte utilisateur :
 * - Affichage des commandes de l’utilisateur connecté
 * - Activation / désactivation de l’accès API
 * - Suppression complète du compte utilisateur
 *
 * Elle hérite d’AbstractController pour bénéficier des fonctionnalités Symfony
 * telles que getUser(), render(), redirectToRoute(), ou la gestion des erreurs.
 *
 * Méthodes principales :
 * - index()       : affiche les commandes de l’utilisateur, triées par date décroissante
 * - toggleApi()   : active ou désactive l’accès API du compte
 * - delete()      : supprime le compte utilisateur et ses commandes associées
 */
final class AccountController extends AbstractController
{
    /**
     * Affiche la liste des commandes de l’utilisateur connecté.
     *
     * Fonctionnement :
     * - Récupère l’utilisateur connecté via getUser()
     * - Récupère ses commandes (Collection Doctrine)
     * - Convertit la Collection en tableau pour permettre un tri PHP
     * - Trie les commandes par date décroissante (plus récentes en premier)
     * - Affiche la page Twig correspondante
     */
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();// Récupération de l’utilisateur connecté.
        
        // Récupération des commandes de l'utilisateur
        $orders = $user->getOrders();
        
        // Conversion en tableau php pour tri avec usort()
        $ordersArray = $orders->toArray();
        
        // Tri des commandes par date décroissante (plus récentes en premier)
        usort($ordersArray, function($a, $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        
        // Affichage de la page du compte avec les commandes triées.
        return $this->render('account/index.html.twig', [
            'orders' => $ordersArray,
        ]);
    }

    /**
     * Active ou désactive l’accès API pour l’utilisateur connecté.
     *
     * Fonctionnement :
     * - Vérifie le token CSRF pour sécuriser la requête
     * - Récupère l’utilisateur connecté via getUser()
     * - Inverse l’état de l’accès API (bool)
     * - Sauvegarde la modification en base de données
     * - Redirige vers la page du compte
     */
    #[Route('/account/api-toggle', name: 'app_account_api_toggle', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleApi(Request $request, EntityManagerInterface $em): Response
    {
        // Vérification du token CSRF pour sécuriser l’action.
        if (!$this->isCsrfTokenValid('api_toggle', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        /** @var User $user */
        $user = $this->getUser();// Récupération de l’utilisateur connecté.

        // Inversion de l’état d’accès API (activation ↔ désactivation).
        $user->setApiAccess(!$user->isApiAccess());
        // Sauvegarde de la modification en base.
        $em->flush();

        // Retour à la page du compte.
        return $this->redirectToRoute('app_account');
    }

    /**
     * Supprime le compte utilisateur et toutes les commandes associées.
     *
     * Fonctionnement :
     * - Vérifie le token CSRF pour sécuriser la requête
     * - Récupère l’utilisateur connecté via getUser()
     * - Supprime toutes les commandes de l’utilisateur
     * - Supprime l’utilisateur lui-même
     * - Déconnecte l’utilisateur
     * - Redirige vers la page d’accueil
     */
    #[Route('/account/delete', name: 'app_account_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        // Vérification du token CSRF pour sécuriser la suppression du compte
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('account_delete', $token)) {
            throw $this->createAccessDeniedException('Token CSRF invalide');
        }
        
        // Récupération de l'utilisateur connecté
        /** @var User $user */  //évite pb reconnaissance getOrders() par l'IDE
        $user = $this->getUser();
        
        // Supprime toutes les commandes de l'utilisateur
        foreach ($user->getOrders() as $order) {
            $em->remove($order);
        }
        
        // Supprime l'utilisateur lui-même en base de données       
        $em->remove($user);
        $em->flush();
        
        // Déconnexion de l'utilisateur
        $security->logout(false);

        // Redirection vers la page d’accueil après suppression du compte.
        return $this->redirectToRoute('app_home');
    }
}
