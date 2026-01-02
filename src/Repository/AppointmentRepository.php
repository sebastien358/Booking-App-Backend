<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\Staff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findForStaffBetween(Staff $staff, \DateTimeImmutable $start, \DateTimeImmutable $end): array {
        return $this->createQueryBuilder('a')
            ->andWhere('a.staff = :staff')
            ->andWhere('a.startAt < :end')
            ->andWhere('a.endAt > :start')
            ->setParameter('staff', $staff)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('a.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function hasConflict(Staff $staff, \DateTimeImmutable $start, \DateTimeImmutable $end): bool {
        return (bool) $this->createQueryBuilder('a')
            ->andWhere('a.staff = :staff')
            ->andWhere('a.startAt < :end')
            ->andWhere('a.endAt > :start')
            ->setParameter('staff', $staff)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllAppointments(int $limit, int $offset)
    {
        return $this->createQueryBuilder('a')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->orderBy('a.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllAppointmentsSearch(string $search): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.staff', 's')
            ->andWhere('s.firstname LIKE :search OR s.lastname LIKE :search')
            ->setParameter('search', '%' . $search . '%')
            ->orderBy('s.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Appointment[] Returns an array of Appointment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Appointment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
