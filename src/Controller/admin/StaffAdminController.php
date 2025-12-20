<?php

namespace App\Controller\admin;

use App\Entity\Picture;
use App\Entity\Staff;
use App\Form\StaffType;
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

#[Route('/api/admin/staff')]
class StaffAdminController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private UploadFileService $uploadFileService;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, UploadFileService $uploadFileService, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->uploadFileService = $uploadFileService;
        $this->logger = $logger;
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