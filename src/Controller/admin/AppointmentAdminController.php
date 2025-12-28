<?php

namespace App\Controller\admin;

use App\Entity\Appointment;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/admin/appointment')]
class AppointmentAdminController extends AbstractController
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
            $appointments = $this->entityManager->getRepository(Appointment::class)->findAllAppointments();

            $dataAppointments = $serializer->normalize($appointments, 'json', ['groups' => ['appointments']]);
            return new JsonResponse($dataAppointments);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur liste des rendez-vous clients : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/search', methods: ['GET'])]
    public function search(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $search = $request->query->get('search');

            $appointments = $this->entityManager->getRepository(Appointment::class)->findAllAppointmentsSearch($search);

            $dataAppointments = $serializer->normalize($appointments, 'json', ['groups' => ['appointments']]);
            return new JsonResponse($dataAppointments, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur liste des rendez-vous clients : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/show/{id}', methods: ['GET'])]
    public function show(int $id, SerializerInterface $serializer): JsonResponse
    {
        try {
            $appointment = $this->entityManager->getRepository(Appointment::class)->find($id);
            if (!$appointment) {
                return new JsonResponse(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
            }

            $appointment->setIsRead(true);

            $dataAppointment = $serializer->normalize($appointment, 'json', ['groups' =>
                ['appointment', 'service', 'staff'], 'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $this->entityManager->flush();

            return new JsonResponse($dataAppointment);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur du details d\'un rendez-vous : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/delete/{id}', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $appointment = $this->entityManager->getRepository(Appointment::class)->find($id);
            if (!$appointment) {
                return new JsonResponse(['error' => 'Rendez-vous introuvable'], Response::HTTP_NOT_FOUND);
            }

            $this->entityManager->remove($appointment);
            $this->entityManager->flush();

            return new JsonResponse(null, Response::HTTP_OK);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur du details d\'un rendez-vous : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}