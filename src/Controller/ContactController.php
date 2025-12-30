<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use App\Services\MailerProvider;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/contact')]
class ContactController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MailerProvider $mailerProvider;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, MailerProvider $mailerProvider, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->mailerProvider = $mailerProvider;
        $this->logger = $logger;
    }

    #[Route('/create', methods: ['POST'])]
    public function list(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);

            $contact = new Contact();

            $form = $this->createForm(ContactType::class, $contact);
            $form->submit($data);

            if (!$form->isSubmitted() || !$form->isValid()) {
                $errors = $this->getErrorMessages($form);
                return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
            }

            $body = $this->render('emails/contact-notification.html.twig', [
                'name' => $data['firstname'],
                'email' => $data['email'],
                'message' => $data['message'],
            ])->getContent();

            $emailFrom = $this->getParameter('email_from');
            $this->mailerProvider->sendEmail($emailFrom, 'Nouveau message via le formulaire de contact', $body);

            $this->entityManager->persist($contact);
            $this->entityManager->flush();

            return new JsonResponse(['success' => true, 'message' => 'Contact created'], Response::HTTP_CREATED);
        } catch(\Throwable $e) {
            $this->logger->error('Erreur de la récupération des contacts : ', [$e->getMessage()]);
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