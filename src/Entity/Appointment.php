<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['appointments', 'appointment'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['appointments', 'appointment'])]
    private ?\DateTimeImmutable $datetime = null;

    #[ORM\Column(length: 125)]
    #[Groups(['appointments', 'appointment'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 125)]
    #[Groups(['appointments', 'appointment'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['appointments', 'appointment'])]
    private ?string $email = null;

    #[ORM\Column(length: 60)]
    #[Groups(['appointments', 'appointment'])]
    private ?string $phone = null;

    #[ORM\Column(nullable: false)]
    #[Groups(['appointments', 'appointment'])]
    private bool $is_read = false;

    #[ORM\Column(nullable: false)]
    #[Groups(['appointments', 'appointment'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['appointments', 'appointment'])]
    private ?Service $service = null;

    #[ORM\ManyToOne(targetEntity: Staff::class, inversedBy: 'appointments')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['appointments', 'appointment'])]
    private ?Staff $staff = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDatetime(): ?\DateTimeImmutable
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeImmutable $datetime): static
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->is_read;
    }

    public function setIsRead(?bool $is_read): static
    {
        $this->is_read = $is_read;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getStaff(): ?Staff
    {
        return $this->staff;
    }

    public function setStaff(?Staff $staff): static
    {
        $this->staff = $staff;

        return $this;
    }
}
