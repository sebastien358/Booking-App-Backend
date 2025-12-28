<?php

namespace App\Controller\admin;

use App\Entity\Category;
use App\Entity\Service;
use App\Form\ServiceType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $search = $request->query->get('search');
            $services = $this->entityManager->getRepository(Service::class)->findAllServiceSearch($search);

            $dataServices = $serializer->normalize($services, 'json', ['groups' => ['services', 'categories']]);
            return new JsonResponse($dataServices, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur liste des rendez-vous clients : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/show/{id}', methods: ['GET'])]
    public function show(int $id, SerializerInterface $serializer): JsonResponse
    {
        try {
            $service= $this->entityManager->getRepository(Service::class)->find($id);
            if (!$service) {
                return new JsonResponse(['error' => 'Service introuvable'], Response::HTTP_NOT_FOUND);
            }

            $dataService = $serializer->normalize($service, 'json', ['groups' => ['services', 'cat'],
                'circulation_reference_handler', function ($object) {
                    return $object->getId();
                }
            ]);

            return new JsonResponse($dataService, Response::HTTP_OK,);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des contacts : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $service = new Service();

            $form = $this->createForm(ServiceType::class, $service);
            $form->submit($data);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $category = $this->entityManager->getRepository(Category::class)->find($data['category']);
            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }

            $service->setCategory($category);

            $this->entityManager->persist($service);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Service create'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Admin service create error : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $service= $this->entityManager->getRepository(Service::class)->find($id);
            if (!$service) {
                return new JsonResponse(['error' => 'Service introuvable'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($service);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_OK,);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des contacts : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getErrorMessages(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors() as $key => $error) {
            $errors[] = $error->getMessage();
        }
        foreach ($form->all() as $child) {
            if ($child->isSubmitted() && !$child->isValid()) {
                $errors[$child->getName()] = $this->getErrorMessages($child);
            }
        }
        return $errors;
    }
}