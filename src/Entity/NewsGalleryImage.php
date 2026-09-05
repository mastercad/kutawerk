<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name: 'news_gallery_images')]
class NewsGalleryImage
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'galleryImages'), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private NewsPost $post;
    #[ORM\Column(length: 255)] private string $imagePath = '';
    #[ORM\Column(length: 180, nullable: true)] private ?string $caption = null;
    #[ORM\Column] private int $position = 0;
    #[ORM\Column(length: 36, options: ['default' => 'main'])] private string $galleryKey = 'main';
    public function getId(): ?int { return $this->id; }
    public function getPost(): NewsPost { return $this->post; }
    public function setPost(NewsPost $post): self { $this->post = $post; return $this; }
    public function getImagePath(): string { return $this->imagePath; }
    public function setImagePath(string $path): self { $this->imagePath = $path; return $this; }
    public function getCaption(): ?string { return $this->caption; }
    public function setCaption(?string $caption): self { $this->caption = $caption !== null && trim($caption) !== '' ? trim($caption) : null; return $this; }
    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): self { $this->position = $position; return $this; }
    public function getGalleryKey(): string { return $this->galleryKey; }
    public function setGalleryKey(string $galleryKey): self { $this->galleryKey = $galleryKey; return $this; }
}
