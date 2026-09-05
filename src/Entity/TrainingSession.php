<?php
declare(strict_types=1);
namespace App\Entity;
use App\Repository\TrainingSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: TrainingSessionRepository::class)]
#[ORM\Table(name: 'training_sessions')]
#[ORM\Index(name: 'idx_training_weekday_time', columns: ['weekday', 'starts_at'])]
class TrainingSession
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id=null;
    #[ORM\Column(length:190,nullable:true,unique:true)] private ?string $legacyKey=null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable:false,onDelete:'CASCADE')] private Course $course;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable:false)] private Location $location;
    #[ORM\Column] private int $weekday=1;
    #[ORM\Column(type:Types::TIME_IMMUTABLE)] private \DateTimeImmutable $startsAt;
    #[ORM\Column(type:Types::TIME_IMMUTABLE)] private \DateTimeImmutable $endsAt;
    #[ORM\Column(type:Types::DATE_IMMUTABLE,nullable:true)] private ?\DateTimeImmutable $validFrom=null;
    #[ORM\Column(type:Types::DATE_IMMUTABLE,nullable:true)] private ?\DateTimeImmutable $validUntil=null;
    #[ORM\Column(type:Types::TEXT,nullable:true)] private ?string $notes=null;
    #[ORM\Column(length:160,nullable:true)] private ?string $danceStyle=null;
    #[ORM\Column(length:500,nullable:true)] private ?string $legacyTrainerNames=null;
    #[ORM\Column] private bool $active=true;
    public function __construct(){ $this->startsAt=new \DateTimeImmutable('16:00');$this->endsAt=new \DateTimeImmutable('17:00'); }
    public function getId():?int{return $this->id;} public function getLegacyKey():?string{return $this->legacyKey;} public function setLegacyKey(?string $v):self{$this->legacyKey=$v;return $this;} public function getCourse():Course{return $this->course;} public function setCourse(Course $v):self{$this->course=$v;return $this;} public function getLocation():Location{return $this->location;} public function setLocation(Location $v):self{$this->location=$v;return $this;} public function getWeekday():int{return $this->weekday;} public function setWeekday(int $v):self{$this->weekday=$v;return $this;} public function getStartsAt():\DateTimeImmutable{return $this->startsAt;} public function setStartsAt(\DateTimeImmutable $v):self{$this->startsAt=$v;return $this;} public function getEndsAt():\DateTimeImmutable{return $this->endsAt;} public function setEndsAt(\DateTimeImmutable $v):self{$this->endsAt=$v;return $this;} public function getValidFrom():?\DateTimeImmutable{return $this->validFrom;} public function setValidFrom(?\DateTimeImmutable $v):self{$this->validFrom=$v;return $this;} public function getValidUntil():?\DateTimeImmutable{return $this->validUntil;} public function setValidUntil(?\DateTimeImmutable $v):self{$this->validUntil=$v;return $this;} public function getNotes():?string{return $this->notes;} public function setNotes(?string $v):self{$this->notes=$v;return $this;} public function getDanceStyle():?string{return $this->danceStyle;} public function setDanceStyle(?string $v):self{$this->danceStyle=$v;return $this;} public function getLegacyTrainerNames():?string{return $this->legacyTrainerNames;} public function setLegacyTrainerNames(?string $v):self{$this->legacyTrainerNames=$v;return $this;} public function isActive():bool{return $this->active;} public function setActive(bool $v):self{$this->active=$v;return $this;}
}
