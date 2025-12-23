<?php

namespace App\Controller;

use App\Entity\Picture;
use App\Entity\Testimonial;
use App\Form\TestimonialType;
use App\Services\UploadFileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/testimonial')]
class TestimonialController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UploadFileService $uploadFileService;
    private LOggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, UploadFileService $uploadFileService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->uploadFileService = $uploadFileService;
        $this->logger = $logger;
    }

    #[Route('/create', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        $testimonial = new Testimonial();

        $form = $this->createForm(TestimonialType::class, $testimonial);
        $form->submit($request->request->all(), false);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new JsonResponse([
                'errors' => (string) $form->getErrors(true, false)
            ], Response::HTTP_BAD_REQUEST);
        }

        $image = $request->files->get('filename');
        if ($image) {
            $picture = new Picture();
            $newFilename = $this->uploadFileService->upload($image);

            $picture->setFilename($newFilename);
            $picture->setTestimonial($testimonial);
            $testimonial->setPicture($picture);

            $this->entityManager->persist($picture);
        }

        $this->entityManager->persist($testimonial);
        $this->entityManager->flush();

        return new JsonResponse(['ok' => true], Response::HTTP_CREATED);
    }

    private function getErrorMessages(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors(true) as $error) {
            $errors[] = (string) $error->getMessage();
        }

        return $errors;
    }
}