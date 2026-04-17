<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
     * Affiche la liste des commandes de l'utilisateur connecté.
     *
     * Fonctionnement :
     * - Récupère l'utilisateur connecté via getUser()
     * - Délègue la requête et le tri à OrderRepository (SQL ORDER BY)
     * - Affiche la page Twig correspondante
     */
    #[Route('/account', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Récupère les commandes triées par date décroissante 
        // Le tri SQL dans OrderRepository (ORDER BY createdAt DESC).
        $orders = $orderRepository->findByUserOrderedByDate($user);

        return $this->render('account/index.html.twig', [
            'orders' => $orders,
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

        // Inversion de l’état d’accès API (activation ↔ désactivation)
        // isApiAccess renvoie true ou false / ! inverse le valeur.
        // setApiAccess enregistre la nouvelle valeur dans l'objet $user
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
     * - Supprime l’utilisateur (les commandes sont supprimées automatiquement
     *   grâce au mapping cascade: ['remove'] + orphanRemoval: true)
     * - Nettoie le contexte de sécurité avec logout(false)
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
        
        // Supprime l'utilisateur.
        // Doctrine supprime automatiquement les Order associés
        // grâce au cascade: ['remove'] + orphanRemoval: true définis dans l'entité User
        $em->remove($user);
        $em->flush();
        
        // Déconnexion de l'utilisateur
        // Nettoyage du contexte de sécurité.
        // logout(false) évite d’invalider le token CSRF encore actif dans la requête.
        $security->logout(false);

        // Redirection vers la page d’accueil après suppression du compte.
        return $this->redirectToRoute('app_home');
    }
}
