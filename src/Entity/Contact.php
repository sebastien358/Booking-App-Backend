<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['contacts', 'contact'])]
    private ?int $id = null;

    #[ORM\Column(length: 125)]
    #[Groups(['contacts', 'contact'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 125)]
    #[Groups(['contacts', 'contact'])]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contacts', 'contact'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contacts', 'contact'])]
    private ?string $message = null;

    #[ORM\Column]
    #[Groups(['contacts', 'contact'])]
    private bool $is_read = false;

    #[ORM\Column]
    #[Groups(['contacts', 'contact'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;

        return $this;
    }

    public function isRead(): ?bool
    {
        return $this->is_read;
    }

    public function setIsRead(bool $is_read): static
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
}
