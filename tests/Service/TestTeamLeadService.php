<?php

namespace App\Tests\Service;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Service\TeamLeadUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestTeamLeadService extends KernelTestCase
{
    private $entityManager;
    private $userRepository;
    private $teamLeadUserService;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);

        $this->teamLeadUserService = new TeamLeadUserService($this->userRepository, $this->entityManager);
    }

    public function testRegisterTeamLeadUser(): void
    {
        try {
            $roleName = 'Team Lead';
            $user = $this->teamLeadUserService->RegisterTeamLeadUser('Shahriar',
                'shams@exabyting.com',
                'Male',
                'Engineering Manager',
                1234567890,
                'password',
                $roleName);

            print_r($user);

            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('Shahriar', $user->getName());
            $this->assertSame('shams@exabyting.com', $user->getEmail());
            $this->assertTrue(password_verify('password', $user->getPassword()));

            $this->assertTrue($user->getRoles()->exists(function($key, $role) {
                return $role->getName() === 'Team Lead';
            }));
        }catch (\Exception $e)
        {
            echo 'An unexpected error occurred: ' . $e->getMessage();
        }
    }
}

