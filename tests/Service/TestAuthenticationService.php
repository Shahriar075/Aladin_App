<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\AttendanceRepository;
use App\Repository\UserRepository;
use App\Service\GeneralUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestAuthenticationService extends KernelTestCase{
    private $entityManager;
    private $userRepository;
    private $userService;

    private $attendanceRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->attendanceRepository = $container-> get(AttendanceRepository::class);

        $this->userService = new GeneralUserService($this->userRepository, $this->entityManager, $this->attendanceRepository);
    }

}

?>