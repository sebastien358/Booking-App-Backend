<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/user')]
class UserController extends AbstractController
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/me', methods: ['GET'])]
    public function me(SerializerInterface $serializer): JsonResponse
    {
        try {
            $user = $this->getUser();

            $dataUser = $serializer->normalize($user, 'json', ['groups' => 'user']);
            return new JsonResponse($dataUser);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération de l\'utilisateur connecté', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}