<?php

namespace App\Controller\admin;

use App\Entity\Testimonial;
use App\Services\TestimonialService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin/testimonial')]
class TestimonialAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private TestimonialService $testimonialService;
    private LOggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, TestimonialService $testimonialService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->testimonialService = $testimonialService;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function index(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $testimonials = $this->entityManager->getRepository(Testimonial::class)->findAll();
            $testimonials = $this->testimonialService->testimonialDisplay($testimonials, $request, $serializer);

            return new JsonResponse($testimonials, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des témoignages : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $testimonials = $this->entityManager->getRepository(Testimonial::class)->find($id);
            $testimonials = $this->testimonialService->testimonialDisplay($testimonials, $request, $serializer);

            return new JsonResponse($testimonials, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des témoignages : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}