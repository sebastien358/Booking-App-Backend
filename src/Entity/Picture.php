<?php

namespace App\Entity;

use App\Repository\PictureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: PictureRepository::class)]
class Picture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['picture'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['picture'])]
    private ?string $filename = null;

    #[ORM\OneToOne(inversedBy: 'picture')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['picture'])]
    private ?Staff $staff = null;

    #[ORM\OneToOne(inversedBy: 'picture')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['picture'])]
    private ?Testimonial $testimonial = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): static
    {
        $this->filename = $filename;

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

    public function getTestimonial(): ?Testimonial
    {
        return $this->testimonial;
    }

    public function setTestimonial(?Testimonial $testimonial): static
    {
        $this->testimonial = $testimonial;

        return $this;
    }
}
