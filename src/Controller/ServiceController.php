<?php

namespace App\Controller;

use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/service')]
class ServiceController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function index(SerializerInterface $serializer): JsonResponse
    {
        try {
            $services = $this->entityManager->getRepository(Service::class)->findAll();

            $dataServices = $serializer->normalize($services, 'json', ['groups' => ['services', 'categories'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            return new JsonResponse($dataServices);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des services : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}