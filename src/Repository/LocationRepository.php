<?php
declare(strict_types=1); namespace App\Repository; use App\Entity\Location; use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository; use Doctrine\Persistence\ManagerRegistry; /** @extends ServiceEntityRepository<Location> */ final class LocationRepository extends ServiceEntityRepository {public function __construct(ManagerRegistry $r){parent::__construct($r,Location::class);}}
