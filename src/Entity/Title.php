<?php

namespace App\Entity;

use App\Repository\TitleRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TitleRepository::class)]
#[ORM\Table(name: 'title')]
#[ORM\HasLifecycleCallbacks]
class Title
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $director = '';

    #[ORM\Column(type: 'integer')]
    private int $releaseYear = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationMinutes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function initTimestamps(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): void { $this->title = $title; }

    public function getDirector(): string { return $this->director; }
    public function setDirector(string $director): void { $this->director = $director; }

    public function getReleaseYear(): int { return $this->releaseYear; }
    public function setReleaseYear(int $releaseYear): void { $this->releaseYear = $releaseYear; }

    public function getDurationMinutes(): ?int { return $this->durationMinutes; }
    public function setDurationMinutes(?int $durationMinutes): void { $this->durationMinutes = $durationMinutes; }

    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
