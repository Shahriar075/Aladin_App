<?php

namespace App\Repository;

use App\Entity\Attendance;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attendance>
 */
class AttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendance::class);
    }

    public function findActiveClockIn(User $user): ?Attendance
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.user = :user')
            ->andWhere('a.clockOut IS NULL')
            ->setParameter('user', $user)
            ->orderBy('a.clockIn', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAttendanceForToday(User $user)
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.clockIn >= :startOfDay')
            ->andWhere('a.clockIn < :endOfDay')
            ->setParameter('user', $user)
            ->setParameter('startOfDay', (new \DateTime())->setTime(0, 0, 0))
            ->setParameter('endOfDay', (new \DateTime())->setTime(23, 59, 59))
            ->getQuery()
            ->getOneOrNullResult();
    }
}