<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }

    public function findAllServices(int $limit, int $offset)
    {
        return $this->createQueryBuilder('s')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllServiceSearch(string $search)
    {
        return $this->createQueryBuilder('s')
            ->where('s.name LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();

    }

    //    /**
    //     * @return Service[] Returns an array of Service objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Service
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
