<?php

namespace App\Service;

use App\Entity\Attendance;
use App\Entity\LeaveRequest;
use App\Entity\LeaveNotification;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class ProfileService
{
    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    public function getUserProfile(User $user): array
    {
        $teamLead = $user->getTeamLeadOf();
        $team = $user->getTeam();

        return [
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'gender' => $user->getGender(),
            'designation' => $user->getDesignation(),
            'phone' => $user->getPhone(),
            'team_lead_name' => $teamLead?->getName(),
            'team_name' => $team?->getTeamName(), // Adding team name
        ];
    }

    public function getAllUsers(): array
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        $formattedUsers = [];
        foreach ($users as $user) {
            $formattedUsers[] = [
                'name' => $user->getName(),
                'phone' => $user->getPhone(),
            ];
        }

        return $formattedUsers;
    }

    public function getAttendanceHistory(User $user): array
    {
        $attendances = $this->entityManager->getRepository(Attendance::class)
            ->findBy(['user' => $user], ['clockIn' => 'ASC']);

        $attendanceHistory = [];
        foreach ($attendances as $attendance) {
            $clockInTime = $attendance->getClockIn();
            $clockOutTime = $attendance->getClockOut();

            $duration = 'N/A';
            if ($clockOutTime) {
                $interval = $clockInTime->diff($clockOutTime);
                $duration = $interval->format('%H:%I:%S');
            }

            $attendanceHistory[] = [
                'date' => $clockInTime->format('Y-m-d'),
                'clock_in_time' => $clockInTime->format('H:i:s'),
                'clock_out_time' => $clockOutTime ? $clockOutTime->format('H:i:s') : 'N/A',
                'duration' => $duration,
            ];
        }

        return $attendanceHistory;
    }


    public function getUsersBySearch(string $name): array
    {
        $users = $this->userRepository->findUsersByName($name);

        $userNamesAndPhones = [];
        foreach ($users as $user) {
            $userNamesAndPhones[] = [
                'name' => $user->getName(),
                'phone' => $user->getPhone(),
            ];
        }
        return $userNamesAndPhones;
    }
}
