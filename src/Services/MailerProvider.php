<?php

namespace App\Services;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerProvider
{
    private $mailer;
    private $emailFrom;

    public function __construct(MailerInterface $mailer, string $emailFrom)
    {
        $this->mailer = $mailer;
        $this->emailFrom = $emailFrom;
    }

    public function sendEmail($to, $subject, $body): void
    {
        $email = (new Email())
            ->from($this->emailFrom)
            ->to($to)
            ->subject($subject)
            ->html($body);
        $this->mailer->send($email);
    }
}