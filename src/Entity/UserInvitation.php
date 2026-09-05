<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: 'user_invitations')]
class UserInvitation
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private User $user;
    #[ORM\Column(length: 64, unique: true)] private string $tokenHash;
    #[ORM\Column] private \DateTimeImmutable $expiresAt;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $acceptedAt = null;
    public function __construct(User $user, string $tokenHash, \DateTimeImmutable $expiresAt) { $this->user=$user; $this->tokenHash=$tokenHash; $this->expiresAt=$expiresAt; }
    public function getUser(): User { return $this->user; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function isUsable(): bool { return $this->acceptedAt === null && $this->expiresAt > new \DateTimeImmutable(); }
    public function accept(): void { $this->acceptedAt = new \DateTimeImmutable(); }
}
