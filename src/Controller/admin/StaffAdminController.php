<?php

namespace App\Controller\admin;

use App\Entity\Picture;
use App\Entity\Staff;
use App\Form\StaffType;
use App\Services\StaffService;
use App\Services\UploadFileService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin/staff')]
class StaffAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UploadFileService $uploadFileService;
    private StaffService $staffService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, UploadFileService $uploadFileService,
        StaffService $staffService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->uploadFileService = $uploadFileService;
        $this->staffService = $staffService;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function list(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $staffs = $this->entityManager->getRepository(Staff::class)->findAll();
            $dataStaffs = $this->staffService->staffDisplay($staffs, $request, $serializer);


            return new JsonResponse($dataStaffs, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des salariés : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $search = $request->query->get('search');
            $staffs = $this->entityManager->getRepository(Staff::class)->findAllStaffSearch($search);

            $dataStaffs = $serializer->normalize($staffs, 'json', ['groups' => 'staffs']);
            return new JsonResponse($dataStaffs, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur liste des rendez-vous clients : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/show/{id}', methods: ['GET'])]
    public function show(int $id, Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $staff = $this->entityManager->getRepository(Staff::class)->find($id);
            if (!$staff) {
                return new JsonResponse(['error' => 'Coiffeur introuvable'], Response::HTTP_NOT_FOUND);
            }

            $dataStaff = $this->staffService->staffDisplay($staff, $request, $serializer);

            return new JsonResponse($dataStaff, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des salariés : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/create', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        try {
            $staff = new Staff();

            $form = $this->createForm(StaffType::class, $staff);
            $data = $request->request->all();

            $form->submit($data);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $image = $request->files->get('filename');
            if ($image instanceof UploadedFile) {
                $picture = new Picture();
                $newFilename = $this->uploadFileService->upload($image);
                $picture->setFilename($newFilename);
                $picture->setStaff($staff);
                $staff->setPicture($picture);

                $this->entityManager->persist($picture);
            }

            $this->entityManager->persist($staff);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Un employé a été ajouté'], Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de l\'ajout d\'un salarié : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', methods: ['DELETE'])]
    public function deleteId(int $id): JsonResponse
    {
        try {
            $staff = $this->entityManager->getRepository(Staff::class)->find($id);
            if (!$staff) {
                return new JsonResponse(['error' => 'Coiffeuse introuvable'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($staff);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la suppprésion d\'un des salariés : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}/picture/{pictureId}', methods: ['DELETE'])]
    public function delete(int $id, int $pictureId): JsonResponse
    {
        try {
            $staff = $this->entityManager->getRepository(Staff::class)->find($id);
            if (!$staff) {
                return new JsonResponse(['error' => 'Coiffeuse introuvable'], Response::HTTP_NOT_FOUND);
            }

            $img = $staff->getPicture();

            if ($img !== null && $pictureId !== 0) {
                if ($img->getId() !== $pictureId) {
                    return new JsonResponse(['error' => 'L\'image ne correspond pas aux coiffeuses'], Response::HTTP_NOT_FOUND);
                }
                $this->uploadFileService->deleteFile($img->getFilename());
                $this->entityManager->remove($img);
            }

            $this->entityManager->remove($staff);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la suppprésion d\'un des salariés : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/{id}/toggle', methods: ['POST'])]
    public function toggle(int $id): JsonResponse
    {
        try {
            $staff = $this->entityManager->getRepository(Staff::class)->find($id);
            $staff->setIsActive(!$staff->isActive());

            $this->entityManager->flush();

            return new JsonResponse([
                'id' => $staff->getId(),
                'is_active' => $staff->isActive(),
            ] , Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des salariés : ', [$e->getMessage()]);
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