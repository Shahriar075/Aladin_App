<?php

namespace App\Repository;

use App\Entity\LeaveRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LeaveRequest>
 */
class LeaveRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LeaveRequest::class);
    }

    public function findLeaveRequestsForTeamLead($teamLeadId)
    {
        return $this->createQueryBuilder('lr')
            ->join('lr.user', 'u')
            ->andWhere('lr.teamLead = :teamLeadId')
            ->andWhere('u != :teamLeadUser')
            ->setParameter('teamLeadId', $teamLeadId)
            ->setParameter('teamLeadUser', $teamLeadId)
            ->getQuery()
            ->getResult();
    }
}
