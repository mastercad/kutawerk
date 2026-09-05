<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\DocumentVersionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentVersionRepository::class)]
#[ORM\Table(name: 'document_versions')]
class DocumentVersion
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(name: 'document_key', length: 80)]
    private string $documentKey = '';
    #[ORM\Column(name: 'stored_path', length: 255)]
    private string $storedPath = '';
    #[ORM\Column(name: 'original_name', length: 255)]
    private string $originalName = '';
    #[ORM\Column(name: 'mime_type', length: 100)]
    private string $mimeType = 'application/pdf';
    #[ORM\Column(name: 'file_size')]
    private int $fileSize = 0;
    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validFrom = null;
    #[ORM\Column(name: 'valid_until', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validUntil = null;
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct() { $this->createdAt = new \DateTimeImmutable(); }
    public function getId(): ?int { return $this->id; }
    public function getDocumentKey(): string { return $this->documentKey; }
    public function setDocumentKey(string $value): self { $this->documentKey = trim($value); return $this; }
    public function getStoredPath(): string { return $this->storedPath; }
    public function setStoredPath(string $value): self { $this->storedPath = $value; return $this; }
    public function getOriginalName(): string { return $this->originalName; }
    public function setOriginalName(string $value): self { $this->originalName = $value; return $this; }
    public function getMimeType(): string { return $this->mimeType; }
    public function setMimeType(string $value): self { $this->mimeType = $value; return $this; }
    public function getFileSize(): int { return $this->fileSize; }
    public function setFileSize(int $value): self { $this->fileSize = $value; return $this; }
    public function getValidFrom(): ?\DateTimeImmutable { return $this->validFrom; }
    public function setValidFrom(?\DateTimeImmutable $value): self { $this->validFrom = $value; return $this; }
    public function getValidUntil(): ?\DateTimeImmutable { return $this->validUntil; }
    public function setValidUntil(?\DateTimeImmutable $value): self { $this->validUntil = $value; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function isValidAt(\DateTimeInterface $at): bool { return (!$this->validFrom || $this->validFrom <= $at) && (!$this->validUntil || $this->validUntil >= $at); }
    public function getFormattedSize(): string { $kb=$this->fileSize/1024; return $kb>=1024?number_format($kb/1024,1,',','.').' MB':number_format($kb,1,',','.').' KB'; }
}
