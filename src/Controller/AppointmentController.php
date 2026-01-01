<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Service;
use App\Entity\Staff;
use App\Form\AppointmentType;
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
        $date = $request->query->get('date'); // 2026-01-01
        $categoryId = (int) $request->query->get('categoryId');
        $serviceId  = (int) $request->query->get('serviceId');
        $staffId    = (int) $request->query->get('staffId');

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
    public function add(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            if (empty($data['datetime'])) {
                return new JsonResponse(['error' => 'datetime manquant'], 400);
            }

            $start = new \DateTimeImmutable($data['datetime']);

            // 🔹 SERVICE
            $service = $this->entityManager
                ->getRepository(Service::class)
                ->find($data['service_id']);

            if (!$service) {
                return new JsonResponse(['error' => 'Service inexistant'], 400);
            }

            $duration = $service->getDuration(); // en minutes
            $end = $start->modify("+{$duration} minutes");

            // 🔹 STAFF
            $staff = $this->entityManager
                ->getRepository(Staff::class)
                ->find($data['staff_id']);

            if (!$staff) {
                return new JsonResponse(['error' => 'Staff inexistant'], 400);
            }

            // 🔹 RECHERCHE DES RDV EXISTANTS DU STAFF
            $qb = $this->entityManager->getRepository(Appointment::class)->createQueryBuilder('a');

            $appointments = $qb
                ->where('a.staff = :staff')
                ->setParameter('staff', $staff)
                ->getQuery()
                ->getResult();

            foreach ($appointments as $existing) {
                $existingStart = $existing->getDatetime();
                $existingEnd = $existingStart->modify(
                    '+' . $existing->getService()->getDuration() . ' minutes'
                );

                // 🔴 CONDITION DE CHEVAUCHEMENT
                if ($start < $existingEnd && $end > $existingStart) {
                    return new JsonResponse(
                        ['error' => 'Ce créneau chevauche un rendez-vous existant'],
                        Response::HTTP_CONFLICT
                    );
                }
            }

            // 🔹 CRÉATION DU RDV
            $appointment = new Appointment();
            $appointment->setDatetime($start);
            $appointment->setService($service);
            $appointment->setStaff($staff);
            $appointment->setFirstname($data['firstname']);
            $appointment->setLastname($data['lastname']);
            $appointment->setEmail($data['email']);
            $appointment->setPhone($data['phone']);
            $appointment->setCreatedAt(new \DateTimeImmutable());

            // Notification admin
            $bodyAdmin = $this->render('emails/appointment-admin-notification.html.twig', [
                'name'        => $data['firstname'] . ' ' . $data['lastname'],
                'email'       => $data['email'],
                'prestation'  => $appointment->getService()->getName(),
                'datetime'    => $data['datetime'],
            ])->getContent();

            $emailFrom = $this->getParameter('email_from');
            $this->mailerProvider->sendEmail($emailFrom, 'Confirmation de votre message', $bodyAdmin);

            // Notification client
            $bodyClient = $this->render('emails/appointment-notification.html.twig', [
                'prestation'  => $appointment->getService()->getName(),
                'datetime'    => $data['datetime'],
            ])->getContent();
            $this->mailerProvider->sendEmail($data['email'], 'Confirmation de votre demande de rendez-vous', $bodyClient);

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
