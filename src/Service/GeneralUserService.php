<?php

namespace App\Service;
use App\Entity\Attendance;
use App\Entity\Authentication;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\AttendanceRepository;
use App\Repository\AuthenticationRepository;
use Doctrine\ORM\EntityManagerInterface;

class GeneralUserService{

    private UserRepository $userRepository;
    private $entityManager;
    private AttendanceRepository $attendanceRepository;
    private AuthenticationRepository $authenticationRepository;


    public function __construct(UserRepository $userRepository, EntityManagerInterface $em, AttendanceRepository $attendanceRepository, AuthenticationRepository $authenticationRepository)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $em;
        $this->attendanceRepository = $attendanceRepository;
        $this->authenticationRepository = $authenticationRepository;
    }

    public function signInUser(string $email, string $password): ?User
    {
        try {
            $user = $this->userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                error_log('User not found for email: ' . $email);
                return null;
            }

            if (!password_verify($password, $user->getPassword())) {
                error_log('Password does not match for user: ' . $email);
                return null;
            }

            $authentication = new Authentication();
            $authentication->setUser($user);
            $authentication->setSignIn(new \DateTime());

            $this->entityManager->beginTransaction();

            try {
                $this->entityManager->persist($authentication);
                $this->entityManager->flush();

                $this->entityManager->commit();

                return $user;
            } catch (\Exception $e) {
                $this->entityManager->rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            return null;
        }
    }


    public function RegisterGeneralUser(string $name, string $email, string $gender, string $designation, int $phone, string $password, string $roleName, User $admin, ?User $teamLead = null) : User
    {
        try {
            if (!$this->isAdmin($admin)) {
                throw new \Exception('Only administrators are allowed to register new users');
            }

            $existingUser = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                throw new \Exception('User with the same email already exists');
            }

            $role = $this->entityManager->getRepository(Role::class)->findOneBy(['name' => $roleName]);
            if (!$role) {
                $role = new Role();
                $role->setName($roleName);
                $this->entityManager->persist($role);
                $this->entityManager->flush();
            }

            $user = new User();
            $user->setName($name);
            $user->setEmail($email);
            $user->setGender($gender);
            $user->setDesignation($designation);
            $user->setPhone($phone);
            $user->addRole($role);
            $user->setCreatedAt(new \DateTime());
            $user->setUpdatedAt(new \DateTime());
            $user->setCreatedBy($admin->getName());
            $user->setUpdatedBy($admin->getName());

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $user->setPassword($hashedPassword);

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            return $user;
        } catch (\Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    public function ClockIn(User $user, \DateTime $createdAt): void
    {
        $activeAuthentication = $this->authenticationRepository->findActiveAuthentication($user);

        if (!$activeAuthentication) {
            throw new \RuntimeException('User is not signed in. Please sign in before clocking in.');
        }
        $existingAttendance = $this->attendanceRepository->findAttendanceForToday($user);

        if ($existingAttendance) {
            throw new \RuntimeException('User has already clocked in for today.');
        }

        $attendance = new Attendance();
        $attendance->setUser($user);
        $attendance->setClockIn($createdAt);
        $attendance->setClockOut(null);
        $attendance->setCreatedBy($user->getName());
        $attendance->setCreatedAt(new \DateTime());
        $attendance->setUpdatedBy($user->getName());
        $attendance->setUpdatedAt(new \DateTime());

        $this->entityManager->beginTransaction();

        try {
            $this->entityManager->persist($attendance);
            $this->entityManager->flush();

            $this->entityManager->commit();
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }
    }

    public function ClockOut(User $user, \DateTime $clockOutTime): void
    {
        $attendance = $this->attendanceRepository->findActiveClockIn($user);
        $existingAttendance = $this->attendanceRepository->findAttendanceForToday($user);

        if (!$existingAttendance) {
            throw new \RuntimeException('User has not clocked in yet.');
        }

        if (!$attendance) {
            throw new \RuntimeException('User has already clocked out today.');
        }

        $attendance->setClockOut($clockOutTime);

        $this->entityManager->flush();
    }

    public function isAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roles = $user->getRoles();

        foreach ($roles as $role) {
            if ($role === 'Admin') {
                return true;
            }
        }

        return false;
    }

    public function isTeamLead(User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roles = $user->getRoles();

        foreach ($roles as $role) {
            if ($role === 'Team Lead') {
                return true;
            }
        }

        return false;
    }

}





?>