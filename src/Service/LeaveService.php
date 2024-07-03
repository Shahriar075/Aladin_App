<?php

namespace App\Service;

use App\Entity\LeaveRequest;
use App\Entity\LeaveNotification;
use App\Entity\Team;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class LeaveService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function applyLeave(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate, string $leaveReason): void
    {
        $teamLead = $user->getTeamLeadOf();

        $existingApprovedLeave = $this->entityManager->getRepository(LeaveRequest::class)->findOneBy([
            'user' => $user,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'status' => 'approved',
        ]);

        if ($existingApprovedLeave) {
            throw new \Exception('You already have an approved leave for these dates.');
        }

        $leave = new LeaveRequest();
        $leave->setUser($user);
        $leave->setTeamLead($teamLead);
        $leave->setStartDate($startDate);
        $leave->setEndDate($endDate);
        $leave->setLeaveReason($leaveReason);
        $leave->setStatus('pending');
        $leave->setCreatedBy($user->getName());
        $leave->setCreatedAt(new \DateTime());

        $this->entityManager->persist($leave);
        $this->entityManager->flush();

        if ($teamLead) {
            $notification = new LeaveNotification();
            $notification->setUser($user);
            $notification->setTeamLead($teamLead);
            $notification->setMessage("Leave request from {$user->getName()}");
            $notification->setStatus('pending'); // or null initially
            $notification->setCreatedBy($user->getName());
            $notification->setCreatedAt(new \DateTime());
            $notification->setIsRead(false);

            $this->entityManager->persist($notification);
            $this->entityManager->flush();
        }
    }
    
    public function approveLeave(User $teamLead, LeaveRequest $leave): void
    {
        if ($teamLead !== $leave->getTeamLead()) {
            throw new \InvalidArgumentException('Only the assigned team lead can approve leave requests.');
        }

        $existingLeave = $this->entityManager->getRepository(LeaveRequest::class)->findOneBy([
            'user' => $leave->getUser(),
        ]);

        if (!$existingLeave) {
            throw new \InvalidArgumentException('No leave request found for the user. Approval cannot proceed.');
        }

        $existingLeave = $this->entityManager->getRepository(LeaveRequest::class)->find($leave->getId());

        if (!$existingLeave || $existingLeave->getStatus() !== 'pending') {
            throw new \InvalidArgumentException('Leave request does not exist or is not in a state that can be approved.');
        }

        $existingLeave->setStatus('approved');
        $existingLeave->setUpdatedAt(new \DateTime());
        $existingLeave->setUpdatedBy($teamLead->getName());

        $notification = $this->entityManager->getRepository(LeaveNotification::class)->findOneBy([
            'user' => $leave->getUser(),
            'teamLead' => $leave->getTeamLead(),
            'status' => 'pending'
        ]);

        if ($notification) {
            $notification->setStatus('approved');
            $notification->setMessage("Leave request from {$leave->getUser()->getName()} approved.");
            $notification->setUpdatedAt(new \DateTime());
            $notification->setUpdatedBy($teamLead->getName());
            $notification->setIsRead(true);
        }

        if ($notification) {
            $this->entityManager->flush();
        }
    }

    public function getLeaveRequestsForTeamMembers($userIdOrName): array
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['id' => $userIdOrName]) ?: $this->entityManager->getRepository(User::class)
            ->findOneBy(['name' => $userIdOrName]);

        if (!$user) {
            throw new \InvalidArgumentException("User with ID or name '{$userIdOrName}' not found.");
        }

        $teamId = $user->getTeam()->getId();

        $today = new \DateTime();

        $leaveRequests = $this->entityManager->getRepository(LeaveRequest::class)
            ->createQueryBuilder('lr')
            ->join('lr.user', 'u')
            ->where('u.team = :teamId')
            ->andWhere('lr.endDate >= :today')
            ->setParameter('teamId', $teamId)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $formattedLeaveRequests = [];

        foreach ($leaveRequests as $leaveRequest) {
            $formattedLeaveRequests[] = [
                'user_name' => $leaveRequest->getUser()->getName(),
                'start_date' => $leaveRequest->getStartDate()->format('Y-m-d'),
                'end_date' => $leaveRequest->getEndDate()->format('Y-m-d'),
            ];
        }

        return $formattedLeaveRequests;
    }




}
