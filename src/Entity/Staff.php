<?php

namespace App\Entity;

use App\Repository\StaffRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: StaffRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Staff
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['staffs', 'staff'])]
    private ?int $id = null;

    #[ORM\Column(length: 125)]
    #[Groups(['staffs', 'staff'])]
    private ?string $firstname = null;

    #[ORM\Column(length: 125)]
    #[Groups(['staffs', 'staff'])]
    private ?string $lastname = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['staffs', 'staff'])]
    private ?bool $is_active = null;

    #[ORM\Column]
    #[Groups(['staffs', 'staff'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\OneToMany(targetEntity: Appointment::class, mappedBy: 'staff', cascade: ['remove'], orphanRemoval: true)]
    #[Groups(['staffs', 'staff'])]
    private Collection $appointments;

    #[ORM\OneToOne(targetEntity: Picture::class, mappedBy: 'staff', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['staffs', 'staff'])]
    private ?Picture $picture = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function __construct() {
        $this->appointments = new ArrayCollection();
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

    public function isActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

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

    public function getAppointments(): Collection
    {
        return $this->appointments;
    }

    public function addAppointment(Appointment $appointment): static
    {
        if (!$this->appointments->contains($appointment)) {
            $this->appointments->add($appointment);
            $appointment->setStaff($this);
        }

        return $this;
    }

    public function removeAppointment(Appointment $appointment): static
    {
        if ($this->appointments->removeElement($appointment)) {
            if ($appointment->getStaff() === $this) {
                $appointment->setStaff(null);
            }
        }

        return $this;
    }

    public function getPicture(): ?Picture
    {
        return $this->picture;
    }

    public function setPicture(?Picture $picture): static
    {
        $this->picture = $picture;

        return $this;
    }
}