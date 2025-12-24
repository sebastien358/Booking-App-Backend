<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Service;
use App\Entity\Staff;
use App\Form\AppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

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

    #[Route('/calendar', methods: ['GET'])]
    public function calendar(): JsonResponse
    {
        $appointments = $this->entityManager
            ->getRepository(Appointment::class)
            ->findAll();

        $data = array_map(function (Appointment $a) {

            $start = $a->getDatetime();
            $duration = $a->getService()->getDuration(); // minutes
            $end = $start->modify("+{$duration} minutes");

            return [
                'id' => $a->getId(),
                'start' => $start->format('c'),
                'end' => $end->format('c'),
                'staffId' => $a->getStaff()->getId(),
            ];
        }, $appointments);

        return $this->json($data);
    }

    #[Route('/create', methods: ['POST'])]
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            // 🔹 DATETIME (SEUL CHAMP TEMPS)
            if (empty($data['datetime'])) {
                return new JsonResponse(['error' => 'datetime manquant'], 400);
            }

            $datetime = new \DateTimeImmutable($data['datetime']);

            $appointment = new Appointment();
            $appointment->setDatetime($datetime);

            // 🔹 SERVICE
            $service = $this->entityManager
                ->getRepository(Service::class)
                ->find($data['service_id']);

            if (!$service) {
                return new JsonResponse(['error' => 'Service inexistant'], 400);
            }

            $appointment->setService($service);

            // 🔹 STAFF
            $staff = $this->entityManager
                ->getRepository(Staff::class)
                ->find($data['staff_id']);

            if (!$staff) {
                return new JsonResponse(['error' => 'Staff inexistant'], 400);
            }

            $appointment->setStaff($staff);

            // 🔹 INFOS CLIENT
            $appointment->setFirstname($data['firstname']);
            $appointment->setLastname($data['lastname']);
            $appointment->setEmail($data['email']);
            $appointment->setPhone($data['phone']);
            $appointment->setCreatedAt(new \DateTimeImmutable());

            // 🔹 CONFLIT (datetime + staff)
            $existing = $this->entityManager->getRepository(Appointment::class)->findOneBy([
                'datetime' => $datetime,
                'staff' => $staff,
            ]);

            if ($existing) {
                return new JsonResponse(
                    ['error' => 'Créneau déjà réservé'],
                    Response::HTTP_CONFLICT
                );
            }

            $this->entityManager->persist($appointment);
            $this->entityManager->flush();

            return new JsonResponse(['message' => 'Rendez-vous créé'], 201);

        } catch (\Throwable $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
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
