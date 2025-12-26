<?php

namespace App\Entity;

use App\Repository\TestimonialRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: TestimonialRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Testimonial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['testimonials', 'testimonial'])]
    private ?int $id = null;

    #[ORM\Column(length: 125)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?string $author = null;

    #[ORM\Column(length: 125)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?string $job = null;

    #[Assert\Range(min: 1, max: 5)]
    #[ORM\Column]
    #[Groups(['testimonials', 'testimonial'])]
    private ?int $rating = null;

    #[ORM\Column(length: 255)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?string $message = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?bool $is_published = null;

    #[ORM\Column(nullable: false)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\OneToOne(targetEntity: Picture::class, mappedBy: 'testimonial', cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[Groups(['testimonials', 'testimonial'])]
    private ?Picture $picture = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function setAuthor(string $author): static
    {
        $this->author = $author;

        return $this;
    }

    public function getJob(): ?string
    {
        return $this->job;
    }

    public function setJob(string $job): static
    {
        $this->job = $job;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

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

    public function getIsPublished(): ?bool
    {
        return $this->is_published;
    }

    public function setIsPublished(bool $is_published): static
    {
        $this->is_published = $is_published;

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
