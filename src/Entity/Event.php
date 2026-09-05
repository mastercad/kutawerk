<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
#[ORM\Index(name: 'idx_events_date', columns: ['event_date'])]
#[ORM\HasLifecycleCallbacks]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 190, nullable: true, unique: true)]
    private ?string $legacyKey = null;

    #[ORM\Column(length: 160)]
    private string $title = '';

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $eventDate;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $eventTime = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $originalDateLabel = null;

    #[ORM\Column]
    private bool $active = true;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] private ?\DateTimeImmutable $visibleFrom = null;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] private ?\DateTimeImmutable $visibleUntil = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToOne]
    private ?Department $department = null;

    #[ORM\ManyToOne]
    private ?Course $course = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    public function __construct()
    {
        $this->eventDate = new \DateTimeImmutable('today');
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getLegacyKey(): ?string { return $this->legacyKey; }
    public function setLegacyKey(?string $value): self { $this->legacyKey = $value; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDate(): \DateTimeImmutable { return $this->eventDate; }
    public function setDate(\DateTimeImmutable $date): self { $this->eventDate = $date; return $this; }
    public function getTime(): ?\DateTimeImmutable { return $this->eventTime; }
    public function setTime(?\DateTimeImmutable $time): self { $this->eventTime = $time; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): self { $this->location = $location; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $link): self { $this->link = $link; return $this; }
    public function getOriginalDateLabel(): ?string { return $this->originalDateLabel; }
    public function setOriginalDateLabel(?string $value): self { $this->originalDateLabel = $value; return $this; }
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $value): self { $this->active = $value; return $this; }
    public function getVisibleFrom(): ?\DateTimeImmutable { return $this->visibleFrom; }
    public function setVisibleFrom(?\DateTimeImmutable $value): self { $this->visibleFrom = $value; return $this; }
    public function getVisibleUntil(): ?\DateTimeImmutable { return $this->visibleUntil; }
    public function setVisibleUntil(?\DateTimeImmutable $value): self { $this->visibleUntil = $value; return $this; }
    public function isVisibleAt(?\DateTimeImmutable $at=null): bool { $at??=new \DateTimeImmutable('now',new \DateTimeZone('Europe/Berlin'));return $this->active&&(!$this->visibleFrom||$this->visibleFrom<=$at)&&(!$this->visibleUntil||$this->visibleUntil>=$at); }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getDepartment(): ?Department { return $this->department; }
    public function setDepartment(?Department $department): self { $this->department = $department; return $this; }
    public function getCourse(): ?Course { return $this->course; }
    public function setCourse(?Course $course): self { $this->course = $course; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $user): self { $this->createdBy = $user; return $this; }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
