<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Contrôleur responsable de la gestion de l’authentification utilisateur.
 *
 * Cette classe gère :
 * - l’affichage du formulaire de connexion
 * - la récupération des erreurs d’authentification
 * - la récupération du dernier identifiant saisi
 * - la route de déconnexion (interceptée automatiquement par Symfony)
 *
 * Le processus d’authentification est entièrement géré par le firewall
 * configuré dans security.yaml. Le contrôleur se limite à l’affichage
 * et à la gestion des informations utiles pour la vue.
 */
class SecurityController extends AbstractController
{
    /**
     * Affiche le formulaire de connexion et transmet les informations utiles.
     *
     * Fonctionnement :
     * - AuthenticationUtils permet de récupérer :
     *     • la dernière erreur d’authentification (si échec)
     *     • le dernier nom d’utilisateur saisi
     * - Ces informations sont envoyées à la vue Twig pour préremplir le champ
     *   et afficher un message d’erreur si nécessaire.
     */
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupération de la dernière erreur de connexion, s’il y en a une.
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupération du dernier identifiant saisi par l’utilisateur.
        $lastUsername = $authenticationUtils->getLastUsername();

        // Transmission des données à la vue Twig.
        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Route de déconnexion.
     *
     * Cette méthode ne sera jamais exécutée :
     * - Le firewall de Symfony intercepte automatiquement cette route
     * - La déconnexion est gérée par la configuration security.yaml
     *
     * Si un utilisateur accède à cette route, une exception est levée
     */
    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
