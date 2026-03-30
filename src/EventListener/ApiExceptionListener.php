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

final class ApiExceptionListener
{
    #[AsEventListener]
    public function onExceptionEvent(ExceptionEvent $event): void
    {
        // N'intercepte que les routes /api
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();

        [$status, $message] = match (true) {
            $exception instanceof NotFoundHttpException        => [Response::HTTP_NOT_FOUND,            'Ressource introuvable.'],
            $exception instanceof AccessDeniedHttpException    => [Response::HTTP_FORBIDDEN,            'Accès refusé.'],
            $exception instanceof UnauthorizedHttpException    => [Response::HTTP_UNAUTHORIZED,         'Authentification requise.'],
            $exception instanceof MethodNotAllowedHttpException => [Response::HTTP_METHOD_NOT_ALLOWED,  'Méthode HTTP non autorisée.'],
            $exception instanceof HttpExceptionInterface       => [$exception->getStatusCode(),         'Erreur HTTP.'],
            default                                            => [Response::HTTP_INTERNAL_SERVER_ERROR, 'Une erreur est survenue.'],
        };

        $event->setResponse(new JsonResponse(['message' => $message], $status));
    }
}

