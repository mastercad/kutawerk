<?php
declare(strict_types=1);namespace App\Service;use Symfony\Bundle\SecurityBundle\Security;use Symfony\Component\HttpFoundation\RequestStack;
final class PreviewClock
{
 public function __construct(private RequestStack $requests,private Security $security){}
 public function now():\DateTimeImmutable{$value=$this->requests->getCurrentRequest()?->query->get('preview_date');if($this->security->getUser()&&is_string($value)&&preg_match('/^\d{4}-\d{2}-\d{2}$/',$value)){return new \DateTimeImmutable($value.' 12:00:00',new \DateTimeZone('Europe/Berlin'));}return new \DateTimeImmutable('now',new \DateTimeZone('Europe/Berlin'));}
 public function isPreview():bool{return $this->security->getUser()!==null&&$this->requests->getCurrentRequest()?->query->has('preview_date');}
}
