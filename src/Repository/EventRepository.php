<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Event> */
final class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /** @return list<Event> */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('event')
            ->orderBy('event.eventDate', 'ASC')
            ->addOrderBy('event.eventTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Event> */
    public function findUpcoming(\DateTimeImmutable $from, ?\DateTimeImmutable $visibleAt = null): array
    {
        return $this->createQueryBuilder('event')
            ->andWhere('event.eventDate >= :from')
            ->andWhere('event.active = true')
            ->andWhere('(event.visibleFrom IS NULL OR event.visibleFrom <= :now)')
            ->andWhere('(event.visibleUntil IS NULL OR event.visibleUntil >= :now)')
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('now', $visibleAt ?? new \DateTimeImmutable('now', new \DateTimeZone('Europe/Berlin')))
            ->orderBy('event.eventDate', 'ASC')
            ->addOrderBy('event.eventTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Event> */
    public function findUpcomingByDepartmentSlug(\DateTimeImmutable $from, string $departmentSlug, ?\DateTimeImmutable $visibleAt = null): array
    {
        return $this->createQueryBuilder('event')
            ->innerJoin('event.department', 'department')
            ->addSelect('department')
            ->leftJoin('event.course', 'course')
            ->addSelect('course')
            ->andWhere('event.eventDate >= :from')
            ->andWhere('event.active = true')
            ->andWhere('(event.visibleFrom IS NULL OR event.visibleFrom <= :now)')
            ->andWhere('(event.visibleUntil IS NULL OR event.visibleUntil >= :now)')
            ->andWhere('department.slug = :departmentSlug')
            ->setParameter('from', $from, Types::DATE_IMMUTABLE)
            ->setParameter('departmentSlug', $departmentSlug)
            ->setParameter('now', $visibleAt ?? new \DateTimeImmutable('now', new \DateTimeZone('Europe/Berlin')))
            ->orderBy('event.eventDate', 'ASC')
            ->addOrderBy('event.eventTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return list<Event> */
    public function findPastByDepartmentSlug(\DateTimeImmutable $before, string $departmentSlug, ?\DateTimeImmutable $visibleAt = null): array
    {
        return $this->createQueryBuilder('event')
            ->innerJoin('event.department', 'department')->addSelect('department')
            ->leftJoin('event.course', 'course')->addSelect('course')
            ->andWhere('event.eventDate < :before')->andWhere('event.active = true')
            ->andWhere('(event.visibleFrom IS NULL OR event.visibleFrom <= :now)')
            ->andWhere('(event.visibleUntil IS NULL OR event.visibleUntil >= :now)')
            ->andWhere('department.slug = :departmentSlug')
            ->setParameter('before',$before,Types::DATE_IMMUTABLE)->setParameter('departmentSlug',$departmentSlug)
            ->setParameter('now',$visibleAt ?? new \DateTimeImmutable('now',new \DateTimeZone('Europe/Berlin')))
            ->orderBy('event.eventDate','DESC')->addOrderBy('event.eventTime','DESC')
            ->getQuery()->getResult();
    }

    public function save(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->persist($event);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $event, bool $flush = true): void
    {
        $this->getEntityManager()->remove($event);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @return list<Event> */
    public function findForUser(User $user): array
    {
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return $this->findAllOrdered();
        }
        return $this->createQueryBuilder('event')->andWhere('event.department IN (:departments)')->setParameter('departments', $user->getDepartments()->toArray())->orderBy('event.eventDate', 'ASC')->getQuery()->getResult();
    }
}
