<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\DocumentVersion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DocumentVersionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, DocumentVersion::class); }

    public function findCurrent(string $key, \DateTimeImmutable $at): ?DocumentVersion
    {
        return $this->createQueryBuilder('d')->andWhere('d.documentKey = :key')->andWhere('(d.validFrom IS NULL OR d.validFrom <= :at)')->andWhere('(d.validUntil IS NULL OR d.validUntil >= :at)')->setParameter('key',$key)->setParameter('at',$at)->orderBy('d.validFrom','DESC')->addOrderBy('d.createdAt','DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    /** @return DocumentVersion[] */
    public function findVersions(string $key): array { return $this->findBy(['documentKey'=>$key],['validFrom'=>'DESC','createdAt'=>'DESC']); }
}
