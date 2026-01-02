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
        $date       = $request->query->get('date'); // YYYY-MM-DD
        $categoryId = (int) $request->query->get('categoryId');
        $serviceId  = (int) $request->query->get('serviceId');
        $staffId    = (int) $request->query->get('staffId');

        if (!$date || !$categoryId || !$serviceId || !$staffId) {
            return $this->json([]);
        }

        $staff   = $this->entityManager->getRepository(Staff::class)->find($staffId);
        $service = $this->entityManager->getRepository(Service::class)->find($serviceId);

        if (!$staff || !$service || $service->getCategory()->getId() !== $categoryId) {
            return $this->json([]);
        }

        $tzParis = new \DateTimeZone('Europe/Paris');
        $tzUtc   = new \DateTimeZone('UTC');

        $duration = (int) $service->getDuration();

        $dayStartParis = new \DateTimeImmutable("$date 09:00:00", $tzParis);
        $dayEndParis   = new \DateTimeImmutable("$date 21:00:00", $tzParis);
        $nowParis      = new \DateTimeImmutable('now', $tzParis);
        $isToday       = $dayStartParis->format('Y-m-d') === $nowParis->format('Y-m-d');

        // RDV du staff sur la journée (stockés en UTC)
        $appointments = $this->entityManager
            ->getRepository(Appointment::class)
            ->findForStaffBetween(
                $staff,
                $dayStartParis->setTimezone($tzUtc),
                $dayEndParis->setTimezone($tzUtc)
            );

        $slots = [];

        for ($slotStartParis = $dayStartParis; $slotStartParis < $dayEndParis; $slotStartParis = $slotStartParis->modify('+15 minutes')) {

            if ($isToday && $slotStartParis <= $nowParis) {
                continue;
            }

            $slotEndParis = $slotStartParis->modify("+{$duration} minutes");

            // si le service dépasse la fin de journée → on ignore CE créneau
            if ($slotEndParis > $dayEndParis) {
                continue;
            }

            // ✅ compare en UTC
            $slotStartUtc = $slotStartParis->setTimezone($tzUtc);
            $slotEndUtc   = $slotEndParis->setTimezone($tzUtc);

            $blocked = false;
            foreach ($appointments as $a) {
                // a->getStartAt() / getEndAt() supposés UTC
                if ($slotStartUtc < $a->getEndAt() && $slotEndUtc > $a->getStartAt()) {
                    $blocked = true;
                    break;
                }
            }

            if ($blocked) {
                continue;
            }

            // ✅ on renvoie en Paris (pour affichage)
            $slots[] = [
                'start' => $slotStartParis->format('c'), // ex: 2026-01-02T17:30:00+01:00
                'label' => $slotStartParis->format('H:i'),
            ];
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

        // 🔒 FUSEAUX
        $tzUtc = new \DateTimeZone('UTC');

        // ✅ datetime ISO venant du front → UTC
        try {
            $startUtc = (new \DateTimeImmutable($data['datetime']))->setTimezone($tzUtc);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Datetime invalide'], 400);
        }

        // 🔹 SERVICE
        $service = $this->entityManager
            ->getRepository(Service::class)
            ->find($data['service_id']);

        if (!$service) {
            return $this->json(['error' => 'Service invalide'], 400);
        }

        $duration = (int) $service->getDuration(); // minutes
        $endUtc = $startUtc->modify("+{$duration} minutes");

        // 🔹 STAFF
        $staff = $this->entityManager
            ->getRepository(Staff::class)
            ->find($data['staff_id']);

        if (!$staff) {
            return $this->json(['error' => 'Staff invalide'], 400);
        }

        // 🔒 BLOCAGE DES DOUBLONS (UTC vs UTC)
        $conflict = $this->entityManager
            ->getRepository(Appointment::class)
            ->hasConflict($staff, $startUtc, $endUtc);

        if ($conflict) {
            return $this->json(
                [
                    'type' => 'DATETIME_ALREADY_TAKEN',
                    'message' => 'Ce créneau est déjà réservé'
                ],
                Response::HTTP_CONFLICT
            );
        }

        // ✅ CRÉATION
        $appointment = new Appointment();
        $appointment->setStartAt($startUtc); // UTC
        $appointment->setEndAt($endUtc);     // UTC
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
