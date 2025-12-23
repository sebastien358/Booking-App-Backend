<?php

namespace App\Controller\admin;

use App\Entity\Testimonial;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin/testimonial')]
class TestimonialAdminStore extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LOggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function index(SerializerInterface $serializer): JsonResponse
    {
        try {
            $testimonials = $this->entityManager->getRepository(Testimonial::class)->findAll();
            $testimonials = $serializer->normalize($testimonials, 'json', ['groups' => 'testimonials']);

            return new JsonResponse($testimonials, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des témoignages : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}