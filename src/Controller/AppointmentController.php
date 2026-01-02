<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Service;
use App\Entity\Staff;
use App\Services\MailerProvider;
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
    private MailerProvider $mailerProvider;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager, MailerProvider $mailerProvider, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->mailerProvider = $mailerProvider;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function list(SerializerInterface $serializer): JsonResponse
    {
        try {
            $appointments = $this->entityManager->getRepository(Appointment::class)->findAll();

            $dataAppointments = $serializer->normalize($appointments, 'json', ['groups' => ['appointments']]);
            return new JsonResponse($dataAppointments);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur liste des rendez-vous clients : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/slots', methods: ['GET'])]
    public function slots(Request $request): JsonResponse
    {
        $date = $request->query->get('date');
        $categoryId = (int) $request->query->get('categoryId');
        $serviceId = (int) $request->query->get('serviceId');
        $staffId = (int) $request->query->get('staffId');

        if (!$date || !$categoryId || !$serviceId || !$staffId) {
            return $this->json([]);
        }

        $staff = $this->entityManager->getRepository(Staff::class)->find($staffId);
        $service = $this->entityManager->getRepository(Service::class)->find($serviceId);

        if (!$staff || !$service || $service->getCategory()->getId() !== $categoryId) {
            return $this->json([]);
        }

        $duration = $service->getDuration(); // minutes

        // ✅ FUSEAU HORAIRE UNIQUE
        $tz = new \DateTimeZone('Europe/Paris');

        $dayStart = new \DateTimeImmutable($date . ' 09:00:00', $tz);
        $dayEnd = new \DateTimeImmutable($date . ' 19:00:00', $tz);
        $now = new \DateTimeImmutable('now', $tz);

        $isToday = $dayStart->format('Y-m-d') === $now->format('Y-m-d');

        $appointments = $this->entityManager
            ->getRepository(Appointment::class)
            ->findForStaffBetween($staff, $dayStart, $dayEnd);

        $slots = [];

        for ($t = $dayStart; $t < $dayEnd; $t = $t->modify('+15 minutes')) {

            $startAt = $t;
            $endAt   = $t->modify("+{$duration} minutes");

            // ⛔ Bloquer les créneaux passés aujourd’hui
            if ($isToday && $startAt <= $now) {
                continue;
            }

            if ($endAt > $dayEnd) {
                break;
            }

            $blocked = false;
            foreach ($appointments as $a) {
                if ($startAt < $a->getEndAt() && $endAt > $a->getStartAt()) {
                    $blocked = true;
                    break;
                }
            }

            if (!$blocked) {
                $slots[] = [
                    'start' => $startAt->format('c'),
                    'end'   => $endAt->format('c'),
                    'label' => $startAt->format('H:i'),
                ];
            }
        }

        return $this->json($slots);
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (
            empty($data['datetime']) ||
            empty($data['service_id']) ||
            empty($data['staff_id'])
        ) {
            return $this->json(['error' => 'Données manquantes'], 400);
        }

        $tz = new \DateTimeZone('Europe/Paris');
        $start = new \DateTimeImmutable($data['datetime'], $tz);

        // Service
        $service = $this->entityManager->getRepository(Service::class)->find($data['service_id']);
        if (!$service) {
            return $this->json(['error' => 'Service invalide'], 400);
        }

        $end = $start->modify('+' . $service->getDuration() . ' minutes');

        // Staff
        $staff = $this->entityManager->getRepository(Staff::class)->find($data['staff_id']);
        if (!$staff) {
            return $this->json(['error' => 'Staff invalide'], 400);
        }

        // 🔒 BLOCAGE DES DOUBLONS
        $conflict = $this->entityManager
            ->getRepository(Appointment::class)
            ->hasConflict($staff, $start, $end);

        if ($conflict) {
            return $this->json(
                ['error' => 'Ce créneau est déjà réservé'],
                409
            );
        }

        // Création
        $appointment = new Appointment();
        $appointment->setStartAt($start);
        $appointment->setEndAt($end);
        $appointment->setService($service);
        $appointment->setStaff($staff);
        $appointment->setFirstname($data['firstname']);
        $appointment->setLastname($data['lastname']);
        $appointment->setEmail($data['email']);
        $appointment->setPhone($data['phone']);

        $this->entityManager->persist($appointment);
        $this->entityManager->flush();

        return $this->json(['message' => 'Rendez-vous confirmé'], 201);
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
