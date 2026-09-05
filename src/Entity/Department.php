<?php
declare(strict_types=1);
namespace App\Entity;
use App\Repository\DepartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: DepartmentRepository::class)]
#[ORM\Table(name: 'departments')]
class Department
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 80, unique: true)] private string $slug = '';
    #[ORM\Column(length: 120)] private string $name = '';
    #[ORM\Column] private bool $active = true;
    /** @var Collection<int, User> */ #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'departments')] private Collection $users;
    public function __construct() { $this->users = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $value): self { $this->slug = trim($value); return $this; }
    public function getName(): string { return $this->name; }
    public function setName(string $value): self { $this->name = trim($value); return $this; }
    public function isActive(): bool { return $this->active; }
}
