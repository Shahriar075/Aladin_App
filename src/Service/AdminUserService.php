<?php

namespace App\Service;
use App\Entity\Role;
use App\Entity\Team;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;

class AdminUserService{

    private UserRepository $userRepository;
    private $entityManager;

    private $generalUserService;
    private $teamRepository;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em, TeamRepository $teamRepository, GeneralUserService $generalUserService)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $em;
        $this->teamRepository = $teamRepository;
        $this->generalUserService = $generalUserService;
    }

    public function RegisterAdminUser(string $name, string $email, string $gender, string $designation, int $phone, string $password, string $roleName) : User
    {
        try {
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
            $user->setCreatedBy($user->getName());
            $user->setUpdatedBy($user->getName());

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
    public function assignTeamLeadByEmail(string $teamLeadEmail, string $adminEmail): void
    {
        $admin = $this->userRepository->findOneBy(['email' => $adminEmail]);
        if (!$admin) {
            throw new \Exception("Admin with email '{$adminEmail}' not found.");
        }

        $teamLead = $this->userRepository->findOneBy(['email' => $teamLeadEmail]);
        if (!$teamLead) {
            throw new \Exception("User with email '{$teamLeadEmail}' not found.");
        }

        $teamName = 'GGWP';
        $team = $this->teamRepository->findOneBy(['teamName' => $teamName]);
        if (!$team) {
            $team = new Team();
            $team->setTeamName($teamName);
            $team->setCreatedBy($admin->getName());
            $team->setCreatedAt(new \DateTime());
            $this->entityManager->persist($team);
        }

        $team->addUser($teamLead);
        $team->setTeamLead($teamLead);

        $teamLead->setTeam($team);

        $team->setUpdatedBy($admin->getName());
        $team->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();
    }

    public function assignUserToTeamLead(string $userEmail, string $teamLeadEmail, string $adminEmail): void
    {
        $admin = $this->userRepository->findOneBy(['email' => $adminEmail]);
        if (!$admin || !$this->generalUserService->isAdmin($admin)) {
            throw new \Exception("Admin with email '{$adminEmail}' not found or is not an admin.");
        }

        $teamLead = $this->userRepository->findOneBy(['email' => $teamLeadEmail]);
        if (!$teamLead) {
            throw new \Exception("Team lead with email '{$teamLeadEmail}' not found.");
        }

        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        if (!$user) {
            throw new \Exception("User with email '{$userEmail}' not found.");
        }

        $team = $teamLead->getTeam();
        if (!$team) {
            throw new \Exception("Team lead with email '{$teamLeadEmail}' is not leading any team.");
        }

        try {
            $team->addUser($user);
            $user->setTeam($team);

            $this->entityManager->flush();
        }catch (\Exception $e)
        {
            throw $e;
        }
    }
}


//eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoxNSwiZW1haWwiOiJhZG1pbkBleGFieXRpbmcuY29tIiwiZXhwIjoxNzE5MjMyOTI5fQ.x8ub1qbdXNBF-GO85Y84rr-aKdjoTY242th4GecoqDY


?>