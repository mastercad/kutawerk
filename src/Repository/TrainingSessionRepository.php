<?php
declare(strict_types=1);
namespace App\Repository;
use App\Entity\{Course,Location,TrainingSession,User};
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
/** @extends ServiceEntityRepository<TrainingSession> */
final class TrainingSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry){parent::__construct($registry,TrainingSession::class);}
    /** @return list<TrainingSession> */ public function findOverview(?\DateTimeImmutable $at=null):array{$at??=new \DateTimeImmutable('today',new \DateTimeZone('Europe/Berlin'));return $this->createQueryBuilder('s')->join('s.course','c')->addSelect('c')->join('s.location','l')->addSelect('l')->andWhere('s.active = true')->andWhere('c.active = true')->andWhere('l.active = true')->andWhere('(s.validFrom IS NULL OR s.validFrom <= :at)')->andWhere('(s.validUntil IS NULL OR s.validUntil >= :at)')->andWhere('(c.validFrom IS NULL OR c.validFrom <= :at)')->andWhere('(c.validUntil IS NULL OR c.validUntil >= :at)')->setParameter('at',$at)->orderBy('s.weekday','ASC')->addOrderBy('s.startsAt','ASC')->getQuery()->getResult();}
    /** @return list<TrainingSession> */ public function findAllOrdered():array{return $this->findBy([],['weekday'=>'ASC','startsAt'=>'ASC']);}
    /** @return list<TrainingSession> */ public function findForUser(User $user):array{if(in_array('ROLE_ADMIN',$user->getRoles(),true))return $this->findAllOrdered();return $this->createQueryBuilder('s')->join('s.course','c')->join('c.trainers','t')->andWhere('t = :user')->setParameter('user',$user)->orderBy('s.weekday','ASC')->addOrderBy('s.startsAt','ASC')->getQuery()->getResult();}
    /** @return list<TrainingSession> */ public function findActiveByLocation(Location $location):array{return $this->findActiveFor('s.location',$location);}
    /** @return list<TrainingSession> */ public function findActiveByCourse(Course $course):array{return $this->findActiveFor('s.course',$course);}
    /** @return array<int,int> */ public function countActiveGroupedByLocation():array{$rows=$this->createQueryBuilder('s')->select('IDENTITY(s.location) AS locationId, COUNT(s.id) AS sessionCount')->andWhere('s.active = true')->groupBy('s.location')->getQuery()->getArrayResult();$counts=[];foreach($rows as $row){$counts[(int)$row['locationId']]=(int)$row['sessionCount'];}return $counts;}
    /** @return list<TrainingSession> */ private function findActiveFor(string $field,object $value):array{return $this->createQueryBuilder('s')->join('s.course','c')->addSelect('c')->andWhere($field.' = :value')->andWhere('s.active = true')->setParameter('value',$value)->orderBy('s.weekday','ASC')->addOrderBy('s.startsAt','ASC')->getQuery()->getResult();}
}
