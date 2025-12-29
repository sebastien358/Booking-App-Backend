<?php

namespace App\Repository;

use App\Entity\Staff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Staff>
 */
class StaffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Staff::class);
    }

        public function findAllStaffs(int $limit, int $offset)
        {
            return $this->createQueryBuilder('s')
                ->setFirstResult($offset)
                ->setMaxResults($limit)
                ->orderBy('s.id', 'DESC')
                ->getQuery()
                ->getResult();
        }

        public function findAllStaffSearch(string $search)
        {
            return $this->createQueryBuilder('s')
                ->where('s.firstname LIKE :search OR s.lastname LIKE :search')
                ->setParameter('search', '%' . $search . '%')
                ->orderBy('s.id', 'DESC')
                ->getQuery()
                ->getResult();
        }

    //    /**
    //     * @return Staff[] Returns an array of Staff objects
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

    //    public function findOneBySomeField($value): ?Staff
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
