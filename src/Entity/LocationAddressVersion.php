<?php
declare(strict_types=1);
namespace App\Entity;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
#[ORM\Entity]
#[ORM\Table(name:'location_address_versions')]
#[ORM\Index(name:'idx_location_address_validity',columns:['valid_from','valid_until'])]
class LocationAddressVersion
{
 #[ORM\Id,ORM\GeneratedValue,ORM\Column] private ?int $id=null;
 #[ORM\ManyToOne(inversedBy:'addressVersions'),ORM\JoinColumn(nullable:false,onDelete:'CASCADE')] private Location $location;
 #[ORM\Column(length:160)] private string $street='';
 #[ORM\Column(length:20)] private string $postalCode='';
 #[ORM\Column(length:100)] private string $city='';
 #[ORM\Column(type:Types::TEXT,nullable:true)] private ?string $notes=null;
 #[ORM\Column(type:Types::DATE_IMMUTABLE,nullable:true)] private ?\DateTimeImmutable $validFrom=null;
 #[ORM\Column(type:Types::DATE_IMMUTABLE,nullable:true)] private ?\DateTimeImmutable $validUntil=null;
 public function getId():?int{return $this->id;} public function getLocation():Location{return $this->location;} public function setLocation(Location $v):self{$this->location=$v;return $this;} public function getStreet():string{return $this->street;} public function setStreet(string $v):self{$this->street=trim($v);return $this;} public function getPostalCode():string{return $this->postalCode;} public function setPostalCode(string $v):self{$this->postalCode=trim($v);return $this;} public function getCity():string{return $this->city;} public function setCity(string $v):self{$this->city=trim($v);return $this;} public function getNotes():?string{return $this->notes;} public function setNotes(?string $v):self{$this->notes=$v;return $this;} public function getValidFrom():?\DateTimeImmutable{return $this->validFrom;} public function setValidFrom(?\DateTimeImmutable $v):self{$this->validFrom=$v;return $this;} public function getValidUntil():?\DateTimeImmutable{return $this->validUntil;} public function setValidUntil(?\DateTimeImmutable $v):self{$this->validUntil=$v;return $this;} public function getAddress():string{return trim($this->street.', '.$this->postalCode.' '.$this->city,', ');} public function isValidAt(\DateTimeInterface $at):bool{return(!$this->validFrom||$this->validFrom<=$at)&&(!$this->validUntil||$this->validUntil>=$at);}
}
