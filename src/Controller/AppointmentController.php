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

        // ❌ dimanche / lundi
        $dayOfWeek = (int) (new \DateTimeImmutable($date))->format('N');
        if ($dayOfWeek === 1 || $dayOfWeek === 7) {
            return $this->json([]);
        }

        $staff = $this->entityManager->getRepository(Staff::class)->find($staffId);
        $service = $this->entityManager->getRepository(Service::class)->find($serviceId);

        if (!$staff || !$service || $service->getCategory()->getId() !== $categoryId) {
            return $this->json([]);
        }

        $tzParis = new \DateTimeZone('Europe/Paris');
        $tzUtc = new \DateTimeZone('UTC');

        $duration = (int) $service->getDuration();

        $dayStartParis = new \DateTimeImmutable("$date 09:00:00", $tzParis);
        $dayEndParis = new \DateTimeImmutable("$date 19:00:00", $tzParis);
        $nowParis = new \DateTimeImmutable('now', $tzParis);
        $isToday = $dayStartParis->format('Y-m-d') === $nowParis->format('Y-m-d');

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

            // ⛔ pause déjeuner
            $pauseStart = new \DateTimeImmutable("$date 12:00:00", $tzParis);
            $pauseEnd   = new \DateTimeImmutable("$date 14:00:00", $tzParis);

            if ($slotStartParis >= $pauseStart && $slotStartParis < $pauseEnd) {
                continue;
            }

            $slotEndParis = $slotStartParis->modify("+{$duration} minutes");
            if ($slotEndParis > $dayEndParis) {
                continue;
            }

            $slotStartUtc = $slotStartParis->setTimezone($tzUtc);
            $slotEndUtc = $slotEndParis->setTimezone($tzUtc);

            foreach ($appointments as $a) {
                if ($slotStartUtc < $a->getEndAt() && $slotEndUtc > $a->getStartAt()) {
                    continue 2;
                }
            }

            $slots[] = [
                'start' => $slotStartParis->format('c'),
                'label' => $slotStartParis->format('H:i'),
            ];
        }

        return $this->json($slots);
    }

    #[Route('/create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($data['datetime']) || empty($data['service_id']) || empty($data['staff_id'])) {
                return $this->json(['error' => 'Données manquantes'], Response::HTTP_BAD_REQUEST);
            }

            // 🔒 FUSEAUX
            $tzUtc = new \DateTimeZone('UTC');

            // ✅ datetime ISO venant du front → UTC
            try {
                $startUtc = (new \DateTimeImmutable($data['datetime']))->setTimezone($tzUtc);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Datetime invalide'], Response::HTTP_BAD_REQUEST);
            }

            // 🔹 SERVICE
            $service = $this->entityManager->getRepository(Service::class)->find($data['service_id']);
            if (!$service) {
                return $this->json(['error' => 'Service invalide'], Response::HTTP_BAD_REQUEST);
            }

            $duration = (int) $service->getDuration(); // minutes
            $endUtc = $startUtc->modify("+{$duration} minutes");

            // 🔹 STAFF
            $staff = $this->entityManager->getRepository(Staff::class)->find($data['staff_id']);
            if (!$staff) {
                return $this->json(['error' => 'Staff invalide'], Response::HTTP_BAD_REQUEST);
            }

            // 🔒 BLOCAGE DES DOUBLONS (UTC vs UTC)
            $conflict = $this->entityManager->getRepository(Appointment::class)->hasConflict($staff, $startUtc, $endUtc);
            if ($conflict) {
                return $this->json(['type' => 'DATETIME_ALREADY_TAKEN', 'message' => 'Ce créneau est déjà réservé'], Response::HTTP_CONFLICT);
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

            $startParis = $startUtc->setTimezone(new \DateTimeZone('Europe/Paris'));

            $this->sendAdminNotification($appointment, $data, $startParis);
            $this->sendClientNotification($appointment, $data, $startParis);

            $this->entityManager->persist($appointment);
            $this->entityManager->flush();

            return $this->json(['message' => 'Rendez-vous confirmé'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur creation d\'un rendez-vous clients : ', [$e->getMessage()]);
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function sendAdminNotification(Appointment $appointment, array $data, $startParis): void
    {
        // NOTIFICATION ADMIN
        $bodyAdmin = $this->render('emails/appointment-admin-notification.html.twig', [
            'name' => $data['firstname'] . ' ' . $data['lastname'],
            'email' => $data['email'],
            'datetime' => $startParis,
            'prestation' => $appointment->getService()->getName()
        ])->getContent();

        $this->mailerProvider->sendEmail($this->getParameter('email_from'), 'Nouveau rendez-vous en ligne', $bodyAdmin);
    }

    private function sendClientNotification(Appointment $appointment, array $data, $startParis): void
    {
        // NOTIFICATION CLIENT
        $bodyClient = $this->render('emails/appointment-notification.html.twig', [
            'name' => $data['firstname'] . ' ' . $data['lastname'],
            'datetime' => $startParis,
            'prestation' => $appointment->getService()->getName()
        ])->getContent();

        $this->mailerProvider->sendEmail($data['email'], 'Confirmation de votre rendez-vous', $bodyClient);
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
