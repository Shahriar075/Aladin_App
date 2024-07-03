<?php

namespace App\Tests\Service;

use App\Entity\Role;
use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\TeamRepository;
use App\Repository\UserRepository;
use App\Service\AdminUserService;
use App\Service\GeneralUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestAdminUserService extends KernelTestCase
{
    private $entityManager;
    private $userRepository;
    private $adminUserService;

    private $generalUserService;
    private $teamRepository;

    private $roleRepository;


    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->generalUserService = $container->get(GeneralUserService::class);
        $this->teamRepository = $container->get(TeamRepository::class);
        $this->roleRepository = $container->get(RoleRepository::class); // Get RoleRepository

        // Pass all required dependencies to AdminUserService constructor
        $this->adminUserService = new AdminUserService(
            $this->userRepository,
            $this->entityManager,
            $this->teamRepository,
            $this->generalUserService
        );
    }

    public function testRegisterAdminUser(): void
    {
        try {
            $roleName = 'Admin';
            $user = $this->adminUserService->RegisterAdminUser(
                'Chondona',
                'admin@exabyting.com',
                'Female',
                'Administrative',
                1234567890,
                'password',
                $roleName
            );

            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('Chondona', $user->getName());
            $this->assertSame('admin@exabyting.com', $user->getEmail());
            $this->assertTrue(password_verify('password', $user->getPassword()));

            $this->assertTrue($user->getRoles()->exists(function($key, $role) {
                return $role->getName() === 'Admin';
            }));
        } catch (\Exception $e) {
            $this->fail('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    public function testAssignTeamLeadByEmail(): void
    {
        $adminEmail = 'admin@exabyting.com';
        $admin = $this->userRepository->findOneBy(['email' => $adminEmail]);

        $this->assertNotNull($admin);

        $teamLeadEmail = 'shams@exabyting.com';
        try {
            $this->adminUserService->assignTeamLeadByEmail($teamLeadEmail, $adminEmail);

            $teamLead = $this->userRepository->findOneBy(['email' => $teamLeadEmail]);
            $this->assertNotNull($teamLead);

            $team = $teamLead->getTeam();
            $this->assertNotNull($team);

            $this->assertEquals('GGWP', $team->getTeamName());

            $this->assertEquals($admin->getName(), $team->getUpdatedBy());
            $this->assertInstanceOf(\DateTimeInterface::class, $team->getUpdatedAt());

            $finalTeamCount = count($this->teamRepository->findAll());

        } catch (\Exception $e) {
            $this->fail('An unexpected error occurred: ' . $e->getMessage());
        }
    }

    public function testAssignUserToTeamLead(): void
    {
        $adminEmail = 'admin@exabyting.com';
        $teamLeadEmail = 'shams@exabyting.com';
        $userEmail = 'joy@exabyting.com';

        $admin = $this->userRepository->findOneBy(['email' => $adminEmail]);
        $this->assertNotNull($admin);

        $roleAdmin = $this->roleRepository->findOneBy(['name' => 'admin']);
        if (!$roleAdmin) {
            $roleAdmin = new Role();
            $roleAdmin->setName('admin');
            $this->entityManager->persist($roleAdmin);
            $this->entityManager->flush();
        }

        if (!$admin->getRoles()->contains($roleAdmin)) {
            $admin->addRole($roleAdmin);
            $this->entityManager->flush();
        }

        $teamLead = $this->userRepository->findOneBy(['email' => $teamLeadEmail]);
        $this->assertNotNull($teamLead);

        $user = $this->userRepository->findOneBy(['email' => $userEmail]);
        $this->assertNotNull($user);

        try {
            $this->adminUserService->assignUserToTeamLead($userEmail, $teamLeadEmail, $adminEmail);

            $team = $user->getTeam();
            $this->assertNotNull($team);
            $this->assertEquals('GGWP', $team->getTeamName());

        } catch (\Exception $e) {
            $this->fail('An unexpected error occurred: ' . $e->getMessage());
        }
    }





}

