<?php
declare(strict_types=1);
namespace App\Entity;
use App\Repository\NewsPostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity(repositoryClass: NewsPostRepository::class)]
#[ORM\Table(name: 'news_posts')]
class NewsPost
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\Column(length: 180)] private string $title = '';
    #[ORM\Column(length: 190, unique: true)] private string $slug = '';
    #[ORM\Column(length: 190, nullable: true, unique: true)] private ?string $legacyKey = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $imagePath = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)] private ?string $excerpt = null;
    #[ORM\Column(type: Types::TEXT)] private string $content = '';
    #[ORM\Column] private bool $contentIsHtml = false;
    #[ORM\Column] private bool $published = false;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)] private \DateTimeImmutable $publishedAt;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] private ?\DateTimeImmutable $visibleFrom = null;
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)] private ?\DateTimeImmutable $visibleUntil = null;
    #[ORM\ManyToOne] private ?User $author = null;
    /** @var Collection<int, NewsGalleryImage> */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: NewsGalleryImage::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $galleryImages;
    public function __construct() { $this->publishedAt = new \DateTimeImmutable(); $this->galleryImages = new ArrayCollection(); }
    public function getId(): ?int{return $this->id;} public function getTitle():string{return $this->title;} public function setTitle(string $v):self{$this->title=trim($v);return $this;} public function getSlug():string{return $this->slug;} public function setSlug(string $v):self{$this->slug=trim($v);return $this;} public function getLegacyKey():?string{return $this->legacyKey;} public function setLegacyKey(?string $v):self{$this->legacyKey=$v;return $this;} public function getImagePath():?string{return $this->imagePath;} public function setImagePath(?string $v):self{$this->imagePath=$v;return $this;} public function getExcerpt():?string{return $this->excerpt;} public function setExcerpt(?string $v):self{$this->excerpt=$v;return $this;} public function getContent():string{return $this->content;} public function setContent(string $v):self{$this->content=trim($v);return $this;} public function isContentHtml():bool{return $this->contentIsHtml;} public function setContentIsHtml(bool $v):self{$this->contentIsHtml=$v;return $this;} public function isPublished():bool{return $this->published;} public function setPublished(bool $v):self{$this->published=$v;return $this;} public function getPublishedAt():\DateTimeImmutable{return $this->publishedAt;} public function setPublishedAt(\DateTimeImmutable $v):self{$this->publishedAt=$v;return $this;} public function getVisibleFrom():?\DateTimeImmutable{return $this->visibleFrom;} public function setVisibleFrom(?\DateTimeImmutable $v):self{$this->visibleFrom=$v;return $this;} public function getVisibleUntil():?\DateTimeImmutable{return $this->visibleUntil;} public function setVisibleUntil(?\DateTimeImmutable $v):self{$this->visibleUntil=$v;return $this;} public function isVisibleAt(?\DateTimeImmutable $at=null):bool{$at??=new \DateTimeImmutable('now',new \DateTimeZone('Europe/Berlin'));return $this->published&&(!$this->visibleFrom||$this->visibleFrom<=$at)&&(!$this->visibleUntil||$this->visibleUntil>=$at);} public function setAuthor(?User $v):self{$this->author=$v;return $this;}
    /** @return Collection<int, NewsGalleryImage> */ public function getGalleryImages(): Collection { return $this->galleryImages; }
    public function addGalleryImage(NewsGalleryImage $image): self { if (!$this->galleryImages->contains($image)) { $this->galleryImages->add($image); $image->setPost($this); } return $this; }
    public function removeGalleryImage(NewsGalleryImage $image): self { $this->galleryImages->removeElement($image); return $this; }
}
