<?php

namespace App\Controller;

use App\Entity\Staff;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/staff')]
class StaffController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    #[Route('/list', methods: ['GET'])]
    public function new(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $staffs = $this->entityManager->getRepository(Staff::class)->findAll();

            $dataStaffs = $serializer->normalize($staffs, 'json', ['groups' => ['staff', 'picture'],
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                }
            ]);

            $urlImage = $request->getSchemeAndHttpHost() . '/images/';
            foreach ($dataStaffs as &$dataStaff) {
               if (isset($dataStaff['picture']['filename'])) {
                   $dataStaff['picture']['filename'] = $urlImage . $dataStaff['picture']['filename'];
               }
            }

            return new JsonResponse($dataStaffs, Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des salariés : ', [$e->getMessage()]);
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}