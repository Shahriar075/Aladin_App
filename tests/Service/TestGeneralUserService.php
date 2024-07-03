<?php

namespace App\Tests\Service;
use App\Entity\Attendance;
use App\Entity\Authentication;
use App\Entity\Role;
use App\Entity\User;
use App\Repository\AuthenticationRepository;
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use App\Repository\AttendanceRepository;
use App\Service\GeneralUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TestGeneralUserService extends KernelTestCase{

    private $entityManager;
    private $userRepository;
    private $userService;
    private $attendanceRepository;
    private $authenticationRepository;


    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->userRepository = $container->get(UserRepository::class);
        $this->attendanceRepository = $container-> get(AttendanceRepository::class);
        $this->authenticationRepository = $container->get(AuthenticationRepository::class);

        $this->userService = new GeneralUserService(
            $this->userRepository,
            $this->entityManager,
            $this->attendanceRepository,
            $this->authenticationRepository
        );
    }


    public function testRegisterGeneralUser() : void {
        try {
            $roleName = 'General';

            $adminUser = $this->userRepository->findOneByEmail('admin@exabyting.com');

            if (!$adminUser) {
                throw new \Exception('Admin user not found');
            }

            $user = $this->userService->RegisterGeneralUser(
                'Riyad',
                'riyad@exabyting.com',
                'Male',
                'Trainee',
                1234567890,
                'password',
                $roleName,
                $adminUser
            );


            $this->assertInstanceOf(User::class, $user);

            $this->assertSame('Riyad', $user->getName());
            $this->assertSame('riyad@exabyting.com', $user->getEmail());
            $this->assertTrue(password_verify('password', $user->getPassword()));

            $this->assertTrue($user->getRoles()->exists(function($key, $role) {
                return $role->getName() === 'General';
            }));
        }catch (\Exception $e)
        {
            echo 'An unexpected error occurred: ' . $e->getMessage();
        }
    }


    public function testSignIn(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'riyad@exabyting.com']);
        $this->assertInstanceOf(User::class, $user, 'User not found.');

        try {
            $signInResult = $this->userService->signIn('riyad@exabyting.com');

            $this->assertInstanceOf(User::class, $signInResult, 'Sign-in failed. User not returned.');

            $authentications = $user->getAuthentications();
            $this->assertCount(1, $authentications, 'Multiple authentication records found for the user.');

            $lastAuthentication = $authentications->last();
            $this->assertInstanceOf(\DateTimeInterface::class, $lastAuthentication->getSignIn(), 'Sign-in date not set correctly.');

            echo 'User is signed in.';
        } catch (\Exception $e) {
            $this->fail('Exception occurred: ' . $e->getMessage());
        }
    }

    public function testClockIn(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'riyad@exabyting.com']);
        $this->assertNotNull($user, 'User not found');

        try {
            $clockInTime = new \DateTime();
            $this->userService->ClockIn($user, $clockInTime);
            echo "Successfully clocked in";
        } catch (\RuntimeException $e) {
            echo "User has already clocked in for today";
            $this->assertEquals('User has already clocked in for today.', $e->getMessage());
            return;
        } catch (\Exception $e) {
            echo "Unexpected error: " . $e->getMessage();
            $this->fail('Unexpected Exception thrown.');
            return;
        }

        $attendance = $this->attendanceRepository->findOneBy(['user' => $user]);

        $this->assertNotNull($attendance, 'Attendance record not found');

        if ($attendance) {
            $this->assertInstanceOf(\DateTime::class, $attendance->getClockIn());
            $this->assertSame($clockInTime->format('Y-m-d H:i:s'), $attendance->getClockIn()->format('Y-m-d H:i:s'));
        }
    }


    public function testClockOut(): void
    {
        $user = $this->userRepository->findOneBy(['email' => 'riyad@exabyting.com']);
        $this->assertNotNull($user, 'User not found');

        try {
            $clockOutTime = new \DateTime();
            $this->userService->ClockOut($user, $clockOutTime);
            echo "Successfully clocked out";
        } catch (\RuntimeException $e) {
            echo "User has already clocked out for today";
            $this->assertEquals('User has already clocked out for today.', $e->getMessage());
            return;
        }

        $attendance = $this->attendanceRepository->findOneBy(['user' => $user]);

        $this->assertNotNull($attendance, 'Attendance record not found');

        if ($attendance) {
            $this->assertInstanceOf(\DateTime::class, $attendance->getClockOut());
            $this->assertSame($clockOutTime->format('Y-m-d H:i:s'), $attendance->getClockOut()->format('Y-m-d H:i:s'));
        }
    }
}

?>