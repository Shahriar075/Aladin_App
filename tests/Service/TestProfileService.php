<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\LeaveService;
use App\Service\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestProfileService extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private ProfileService $profileService;

    private LeaveService $leaveRequestService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->profileService = new ProfileService($this->entityManager);
        $this->leaveRequestService = new LeaveService($this->entityManager);
    }

    public function testGetUserProfile(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'ashikur@exabyting.com']);
        $this->assertNotNull($user, 'User with email "ashikur@exabyting.com" not found.');

        try {
            $profileData = $this->profileService->getUserProfile($user);
        } catch (\Exception $e) {
            $this->fail('An unexpected error occurred: ' . $e->getMessage());
        }

        print_r($profileData);

        $this->assertArrayHasKey('id', $profileData);
        $this->assertArrayHasKey('name', $profileData);
        $this->assertArrayHasKey('email', $profileData);
        $this->assertArrayHasKey('gender', $profileData);
        $this->assertArrayHasKey('designation', $profileData);
        $this->assertArrayHasKey('phone', $profileData);
        $this->assertArrayHasKey('team_lead_name', $profileData);
        $this->assertArrayHasKey('team_name', $profileData);

        $this->assertEquals($user->getId(), $profileData['id']);
        $this->assertEquals($user->getName(), $profileData['name']);
        $this->assertEquals($user->getEmail(), $profileData['email']);
        $this->assertEquals($user->getGender(), $profileData['gender']);
        $this->assertEquals($user->getDesignation(), $profileData['designation']);
        $this->assertEquals($user->getPhone(), $profileData['phone']);
        $this->assertEquals($user->getTeamLeadOf() ? $user->getTeamLeadOf()->getName() : null, $profileData['team_lead_name']);
        $this->assertEquals($user->getTeam() ? $user->getTeam()->getTeamName() : null, $profileData['team_name']);
    }

    public function testGetLeaveRequestsForTeamMembersByName()
    {
        $userName = 'Ashikur';

        $leaveRequests = $this->leaveRequestService->getLeaveRequestsForTeamMembers($userName);

        print_r($leaveRequests);

        $this->assertIsArray($leaveRequests);
        $this->assertNotEmpty($leaveRequests);

        foreach ($leaveRequests as $leaveRequest) {
            $this->assertArrayHasKey('user_name', $leaveRequest);
            $this->assertArrayHasKey('start_date', $leaveRequest);
            $this->assertArrayHasKey('end_date', $leaveRequest);
        }
    }

    public function testPrintAllUsers()
    {
        $users = $this->profileService->getAllUsers();
        
        foreach ($users as $user) {
            echo "Name: {$user['name']}, Phone: {$user['phone']}\n";
        }
    }

    public function testGetAttendanceHistory(): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'ashikur@exabyting.com']);
        $this->assertNotNull($user, 'User with email "ashikur@exabyting.com" not found.');

        $attendanceHistory = $this->profileService->getAttendanceHistory($user);

        $this->assertIsArray($attendanceHistory);
        $this->assertNotEmpty($attendanceHistory);

        foreach ($attendanceHistory as $attendance) {
            $this->assertArrayHasKey('date', $attendance);
            $this->assertArrayHasKey('clock_in_time', $attendance);
            $this->assertArrayHasKey('clock_out_time', $attendance);

        }

        print_r($attendanceHistory);
    }

    public function testGetUsersBySearch(): void
    {
        $name = 'Ashikur';

        $users = $this->profileService->getUsersBySearch($name);

        print_r($users);

        $this->assertIsArray($users);
        $this->assertNotEmpty($users);

        foreach ($users as $userInfo) {
            $this->assertArrayHasKey('name', $userInfo);
            $this->assertArrayHasKey('phone', $userInfo);
            $this->assertStringContainsStringIgnoringCase($name, $userInfo['name']);
        }
    }

}
