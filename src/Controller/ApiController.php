<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager,
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            return $this->json(['message' => 'Identifiants manquants.'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $userRepository->findOneBy(['email' => $username]);

        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Identifiants incorrects.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->isApiAccess()) {
            return $this->json(['message' => 'Accès API non activé.'], Response::HTTP_FORBIDDEN);
        }

        $token = $jwtManager->create($user);

        return $this->json(['token' => $token], Response::HTTP_OK);
    }

    #[Route('/api/products', name: 'api_products', methods: ['GET'])]
    public function products(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();

        $data = array_map(fn($p) => [
            'id'               => $p->getId(),
            'name'             => $p->getName(),
            'shortDescription' => $p->getShortDescription(),
            'fullDescription'  => $p->getFullDescription(),
            'price'            => $p->getPrice(),
            'picture'          => $p->getPicture(),
        ], $products);

        return $this->json($data, Response::HTTP_OK);
    }
}
