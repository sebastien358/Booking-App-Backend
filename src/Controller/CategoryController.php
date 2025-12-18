<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/category')]
class CategoryController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/list' , methods: ['GET'])]
    public function index(SerializerInterface $serializer): JsonResponse
    {
        try {
             $categories = $this->entityManager->getRepository(Category::class)->findAll();

             $dataCategories = $serializer->normalize($categories, 'json', ['groups' => 'categories']);
             return new JsonResponse($dataCategories);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des catégories : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}