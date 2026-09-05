<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const PERMISSION_NEWS = 'news.manage';
    public const PERMISSION_TRAINING = 'training.manage';
    public const PERMISSION_EVENTS = 'events.manage';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 180, unique: true, nullable: true)]
    private ?string $email = null;
    #[ORM\Column(length: 100)]
    private string $firstName = '';
    #[ORM\Column(length: 100)]
    private string $lastName = '';
    #[ORM\Column]
    private array $roles = [];
    #[ORM\Column]
    private array $permissions = [];
    #[ORM\Column(nullable: true)]
    private ?string $password = null;
    #[ORM\Column]
    private bool $active = true;
    #[ORM\Column]
    private bool $trainer = false;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trainerImagePath = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $trainerBio = null;
    #[ORM\Column(length: 180, nullable: true)]
    private ?string $contactFunction = null;
    #[ORM\Column]
    private bool $contactPerson = false;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] private ?\DateTimeImmutable $accessFrom = null;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] private ?\DateTimeImmutable $accessUntil = null;
    /** @var Collection<int, Department> */
    #[ORM\ManyToMany(targetEntity: Department::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_departments')]
    private Collection $departments;

    public function __construct() { $this->departments = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $email = $email !== null ? mb_strtolower(trim($email)) : null; $this->email = $email !== '' ? $email : null; return $this; }
    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $value): self { $this->firstName = trim($value); return $this; }
    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $value): self { $this->lastName = trim($value); return $this; }
    public function getDisplayName(): string { return trim($this->firstName.' '.$this->lastName); }
    public function getUserIdentifier(): string { return $this->email ?? ''; }
    public function getRoles(): array { return array_values(array_unique([...$this->roles, 'ROLE_USER'])); }
    public function setRoles(array $roles): self { $this->roles = array_values(array_unique($roles)); return $this; }
    public function getPermissions(): array { return $this->permissions; }
    public function setPermissions(array $permissions): self { $this->permissions = array_values(array_unique($permissions)); return $this; }
    public function hasPermission(string $permission): bool { return in_array('ROLE_ADMIN', $this->getRoles(), true) || in_array($permission, $this->permissions, true); }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): self { $this->password = $password; return $this; }
    public function eraseCredentials(): void {}
    public function isActive(): bool { return $this->active; }
    public function setActive(bool $active): self { $this->active = $active; return $this; }
    public function getAccessFrom(): ?\DateTimeImmutable { return $this->accessFrom; }
    public function setAccessFrom(?\DateTimeImmutable $value): self { $this->accessFrom = $value; return $this; }
    public function getAccessUntil(): ?\DateTimeImmutable { return $this->accessUntil; }
    public function setAccessUntil(?\DateTimeImmutable $value): self { $this->accessUntil = $value; return $this; }
    public function hasAccessAt(?\DateTimeImmutable $at=null): bool { $at??=new \DateTimeImmutable('now',new \DateTimeZone('Europe/Berlin'));return $this->active&&(!$this->accessFrom||$this->accessFrom<=$at)&&(!$this->accessUntil||$this->accessUntil>=$at); }
    public function isTrainer(): bool { return $this->trainer; }
    public function setTrainer(bool $trainer): self { $this->trainer = $trainer; return $this; }
    public function getTrainerImagePath(): ?string { return $this->trainerImagePath; }
    public function setTrainerImagePath(?string $path): self { $this->trainerImagePath = $path; return $this; }
    public function getTrainerBio(): ?string { return $this->trainerBio; }
    public function setTrainerBio(?string $bio): self { $this->trainerBio = $bio !== null && trim($bio) !== '' ? trim($bio) : null; return $this; }
    public function getContactFunction(): ?string { return $this->contactFunction; }
    public function setContactFunction(?string $value): self { $this->contactFunction = $value !== null && trim($value) !== '' ? trim($value) : null; return $this; }
    public function isContactPerson(): bool { return $this->contactPerson; }
    public function setContactPerson(bool $value): self { $this->contactPerson = $value; return $this; }
    /** @return Collection<int, Department> */ public function getDepartments(): Collection { return $this->departments; }
    public function addDepartment(Department $department): self { if (!$this->departments->contains($department)) $this->departments->add($department); return $this; }
    public function removeDepartment(Department $department): self { $this->departments->removeElement($department); return $this; }
    public function clearDepartments(): self { $this->departments->clear(); return $this; }
}
