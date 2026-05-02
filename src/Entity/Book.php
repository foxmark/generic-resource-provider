<?php

namespace App\Entity;

use App\Repository\BookRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use App\EventListener\Doctrine\Interface\NotifiableInsertInterface;
use App\EventListener\Doctrine\Interface\NotifiableUpdatedInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: BookRepository::class)]
#[ORM\Table(name: 'book')]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity(fields: ['isbn'])]
class Book implements NotifiableInsertInterface, NotifiableUpdatedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'string', length: 20, unique: true)]
    private string $isbn = '';

    #[ORM\Column(type: 'string', length: 255)]
    private string $authorName = '';

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $available = true;

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

    public function getIsbn(): string { return $this->isbn; }
    public function setIsbn(string $isbn): void { $this->isbn = $isbn; }

    public function getAuthorName(): string { return $this->authorName; }
    public function setAuthorName(string $authorName): void { $this->authorName = $authorName; }

    public function getPublishedAt(): ?DateTimeImmutable { return $this->publishedAt; }
    public function setPublishedAt(?DateTimeImmutable $publishedAt): void { $this->publishedAt = $publishedAt; }

    public function isAvailable(): bool { return $this->available; }
    public function setAvailable(bool $available): void { $this->available = $available; }

    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}