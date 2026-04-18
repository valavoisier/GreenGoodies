<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Handler déclenché après une authentification réussie sur /api/login.
 *
 * Rôle :
 * - Vérifier que l'utilisateur a activé son accès API (flag apiAccess)
 * - Si oui : déléguer la génération du token JWT au handler Lexik
 * - Si non : retourner une réponse 403 sans générer de token
 *
 * Ce handler est injecté dans le firewall `api` via security.yaml (success_handler).
 */
final class ApiAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(
        private AuthenticationSuccessHandlerInterface $lexikSuccessHandler,
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): Response
    {
        $user = $token->getUser();

        // Vérification du flag apiAccess avant de générer le token JWT.
        // Si l'accès API est désactivé, on retourne 403 sans appeler Lexik.
        if ($user instanceof User && !$user->isApiAccess()) {
            return new JsonResponse(['message' => 'Accès API non activé.'], Response::HTTP_FORBIDDEN);
        }

        // Délégation à Lexik : génération et retour du token JWT.
        return $this->lexikSuccessHandler->onAuthenticationSuccess($request, $token);
    }
}
