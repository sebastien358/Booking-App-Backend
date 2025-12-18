<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Category;
use App\Form\AppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/appointment')]
class AppointmentController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/create', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $appointment = new Appointment();

            $form = $this->createForm(AppointmentType::class, $appointment);
            $form->submit($data);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $date = new \DateTimeImmutable($data['datetime']);

            $dateTime = $this->entityManager->getRepository(Appointment::class)->findOneBy(['datetime' => $date]);
            if ($dateTime) {
                return new JsonResponse(['type' => 'DATETIME_ALREADY_TAKEN', 'error' => 'Ce créneau est déjà réservé'], Response::HTTP_CONFLICT);
            }

            $category = $this->entityManager->getRepository(Category::class)->find($data['category_id']);
            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], Response::HTTP_BAD_REQUEST);
            }

            $appointment->setCategory($category);

            $this->entityManager->persist($appointment);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Rendez vous créé avec succès'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la prise de rendez-vous : ', [$e->getMessage()]);
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