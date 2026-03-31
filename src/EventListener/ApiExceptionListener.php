<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Listener dédié à la gestion des exceptions pour les routes de l’API.
 *
 * Son rôle :
 * - Intercepter les exceptions déclenchées sur les endpoints commençant par /api
 * - Convertir ces erreurs en réponses JSON propres et cohérentes
 * - Fournir un message clair et un code HTTP adapté
 *
 * Ce listener garantit que l’API renvoie toujours une réponse JSON,
 * même en cas d’erreur, au lieu d’une page HTML d’erreur Symfony.
 */
final class ApiExceptionListener
{
     /**
     * Intercepte les exceptions et renvoie une réponse JSON adaptée.
     *
     * Fonctionnement :
     * - Ne traite que les requêtes dont l’URL commence par /api
     * - Analyse le type d’exception levée
     * - Associe un code HTTP et un message clair via un match()
     * - Remplace la réponse par un JsonResponse standardisé
     */
    #[AsEventListener]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        // N'intercepte que les routes /api
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        // Récupération de l’exception levée pendant le traitement de la requête.
        // getThrowable() renvoie tout type d’erreur (Exception ou Error) via l’interface Throwable.
        $exception = $event->getThrowable();

        // Détermination du code HTTP et du message en fonction du type d’erreur.
        // 404 pour NotFound, 403 pour AccessDenied, 401 pour Unauthorized, 
        // 405 pour MethodNotAllowed, 500 erreur serveur et 400 pour les autres erreurs.
        // Le match(true) permet de tester plusieurs conditions successives.
        [$status, $message] = match (true) {
            $exception instanceof NotFoundHttpException        => [Response::HTTP_NOT_FOUND,            'Ressource introuvable.'],
            $exception instanceof AccessDeniedHttpException    => [Response::HTTP_FORBIDDEN,            'Accès refusé.'],
            $exception instanceof UnauthorizedHttpException    => [Response::HTTP_UNAUTHORIZED,         'Authentification requise.'],
            $exception instanceof MethodNotAllowedHttpException => [Response::HTTP_METHOD_NOT_ALLOWED,  'Méthode HTTP non autorisée.'],
            $exception instanceof HttpExceptionInterface       => [$exception->getStatusCode(),         'Erreur HTTP.'],
            default                                            => [Response::HTTP_INTERNAL_SERVER_ERROR, 'Une erreur est survenue.'],
        };

        // Remplacement de la réponse par un JSON propre et standardisé.
        $event->setResponse(new JsonResponse(['message' => $message], $status));
    }
}

