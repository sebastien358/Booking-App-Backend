<?php

namespace App\Controller\admin;

use App\Entity\Testimonial;
use App\Services\TestimonialService;
use App\Services\UploadFileService;
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
    private UploadFileService $uploadFileService;
    private LOggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, TestimonialService $testimonialService,
        UploadFileService $fileService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->testimonialService = $testimonialService;
        $this->uploadFileService = $fileService;
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

    #[Route('/show/{id}', methods: ['GET'])]
    public function show(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $testimonials = $this->entityManager->getRepository(Testimonial::class)->find($id);
            if (!$testimonials) {
                return new JsonResponse(['error' => 'Erreur de la récupération d\'un témoignage'], Response::HTTP_NOT_FOUND);
            }

            $testimonials = $this->testimonialService->testimonialDisplay($testimonials, $request, $serializer);

            return new JsonResponse($testimonials, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des témoignages : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/show/{id}', methods: ['DELETE'])]
    public function deleteId(int $id): JsonResponse
    {
        try {
            $testimonial = $this->entityManager->getRepository(Testimonial::class)->find($id);
            if (!$testimonial) {
                return new JsonResponse(['error' => 'Erreur de la récupération d\'un témoignage'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($testimonial);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des témoignages : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/show/{id}/picture/{pictureId}', methods: ['DELETE'])]
    public function delete(int $id, int $pictureId): JsonResponse
    {
        try {
            $testimonial = $this->entityManager->getRepository(Testimonial::class)->find($id);
            if (!$testimonial) {
                return new JsonResponse(['error' => 'Erreur de la récupération d\'un témoignage'], Response::HTTP_NOT_FOUND);
            }

            $img = $testimonial->getPicture();

            if ($img !== null && $pictureId !== 0) {
                if ($img->getId() !== $pictureId) {
                    return new JsonResponse(['error' => 'L\'image ne correspond pas aux coiffeuses'], Response::HTTP_NOT_FOUND);
                }
                $this->uploadFileService->deleteFile($img->getFilename());
                $this->entityManager->remove($img);
            }

            $this->entityManager->remove($testimonial);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_OK);
        } catch (\Throwable $e) {
            $this->logger->error('Erreur de la récupération des témoignages : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}