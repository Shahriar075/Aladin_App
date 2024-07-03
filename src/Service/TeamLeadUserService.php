<?php

namespace App\Service;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class TeamLeadUserService{

    private UserRepository $userRepository;
    private $entityManager;

    public function __construct(UserRepository $userRepository, EntityManagerInterface $em)
    {
        $this->userRepository = $userRepository;
        $this->entityManager = $em;
    }

    public function RegisterTeamLeadUser(string $name, string $email, string $gender, string $designation, int $phone, string $password, string $roleName) : User
    {
        try {
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

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $user->setPassword($hashedPassword);


            error_log('Persisting user: ' . $name);
            $this->entityManager->persist($user);
            error_log('Flushing data to database');
            $this->entityManager->flush();

            return $user;
        } catch (\Exception $e) {
            error_log('Exception: ' . $e->getMessage());
            throw $e;
        }
    }
}


?>