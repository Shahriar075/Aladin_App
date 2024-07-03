<?php

namespace App\Tests\Service;

use App\Entity\LeaveRequest;
use App\Entity\LeaveNotification;
use App\Entity\User;
use App\Repository\LeaveRequestRepository;
use App\Repository\LeaveNotificationRepository;
use App\Repository\UserRepository;
use App\Service\LeaveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestLeaveService extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $userRepository;
    private LeaveRequestRepository $leaveRequestRepository;
    private LeaveNotificationRepository $notificationRepository;
    private LeaveService $leaveService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->leaveRequestRepository = $this->entityManager->getRepository(LeaveRequest::class);
        $this->notificationRepository = $this->entityManager->getRepository(LeaveNotification::class);

        $this->leaveService = new LeaveService($this->entityManager);
    }

    public function testApplyLeave(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'joy@exabyting.com']);
        $this->assertNotNull($user, 'User with email "joy@exabyting.com" not found.');

        $startDate = new \DateTime('2024-06-20');
        $endDate = new \DateTime('2024-06-22');
        $leaveReason = 'Vacation leave';

        try {
            $this->leaveService->applyLeave($user, $startDate, $endDate, $leaveReason);
        } catch (\Exception $e) {
            $this->fail('An unexpected error occurred: ' . $e->getMessage());
        }

        // Retrieve the created leave entity
        $leaveRequest = $this->leaveRequestRepository->findOneBy([
            'user' => $user,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'leaveReason' => $leaveReason,
        ]);

        $this->assertInstanceOf(LeaveRequest::class, $leaveRequest, 'LeaveRequest entity not found.');

        // Additional assertions
        $this->assertSame($user, $leaveRequest->getUser()); // Ensure user is the same instance
        $this->assertSame($startDate, $leaveRequest->getStartDate());
        $this->assertSame($endDate, $leaveRequest->getEndDate());
        $this->assertSame($leaveReason, $leaveRequest->getLeaveReason());
        $this->assertSame('pending', $leaveRequest->getStatus());

        // Check if a notification was created for the team lead
        $notification = $this->notificationRepository->findOneBy([
            'user' => $user,
            'teamLead' => $user->getTeamLeadOf(), // Assuming you have a method getTeamLead() in User entity
            'status' => 'pending', // Adjust based on your implementation
        ]);

        $this->assertInstanceOf(LeaveNotification::class, $notification, 'LeaveNotification entity not found.');
        $this->assertSame($user, $notification->getUser());
        $this->assertSame($user->getTeamLeadOf(), $notification->getTeamLead());
        $this->assertSame("Leave request from {$user->getName()}", $notification->getMessage());
        $this->assertSame('pending', $notification->getStatus());
    }



    public function testApproveLeave(): void
    {
        try {
            $user = $this->userRepository->findOneBy(['email' => 'joy@exabyting.com']);
            $this->assertNotNull($user, 'User with email "joy@exabyting.com" not found.');

            $teamLead = $user->getTeamLeadOf();
            $this->assertInstanceOf(User::class, $teamLead, 'Team lead not found.');

            $startDate = new \DateTime('2024-06-20');
            $endDate = new \DateTime('2024-06-22');
            $leaveReason = 'Vacation leave';

            $leaveRequest = $this->leaveRequestRepository->findOneBy([
                'user' => $user,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'leaveReason' => $leaveReason,
            ]);

            $this->assertInstanceOf(LeaveRequest::class, $leaveRequest, 'LeaveRequest entity not found.');
            $this->assertSame('pending', $leaveRequest->getStatus(), 'LeaveRequest status should be "pending" before approval.');

            $this->leaveService->approveLeave($teamLead, $leaveRequest);

            $this->assertEquals('approved', $leaveRequest->getStatus(), 'LeaveRequest status not updated to "approved".');

            $notification = $this->notificationRepository->findOneBy([
                'user' => $user,
                'teamLead' => $teamLead,
                'status' => 'approved',
            ]);

            $this->assertInstanceOf(LeaveNotification::class, $notification, 'LeaveNotification entity not found.');
            $this->assertSame('approved', $notification->getStatus(), 'LeaveNotification status not updated to "approved".');
            $this->assertSame("Leave request from {$user->getName()} approved.", $notification->getMessage());

        } catch (\Exception $e) {
            $this->fail('An unexpected error occurred: ' . $e->getMessage());
        }
    }

}

