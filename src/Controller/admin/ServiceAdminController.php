<?php

namespace App\Controller\admin;

use App\Entity\Service;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin/service')]
class ServiceAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function list(SerializerInterface $serializer): JsonResponse
    {
        try {
            $services = $this->entityManager->getRepository(Service::class)->findAllServices();

            $dataServices = $serializer->normalize($services, 'json', ['groups' => ['services', 'categories'],
                'circulation_reference_handler', function ($object) {
                    return $object->getId();
                }
            ]);

            return new JsonResponse($dataServices, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Admin service error : ', [$e->getMessage()]);
        }
    }
}