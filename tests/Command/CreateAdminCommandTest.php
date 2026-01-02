<?php

namespace App\Tests\Command;

use App\Entity\User;
use App\Enum\RoleEnum;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreateAdminCommandTest extends KernelTestCase
{
    private CommandTester $commandTester;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);

        $command = $application->find('app:create:admin');
        $this->commandTester = new CommandTester($command);

        $container = static::getContainer();
        $this->userRepository = $container->get(UserRepository::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->passwordHasher = $container->get(UserPasswordHasherInterface::class);

        // Clean up any existing admin users before each test
        $this->cleanUpAdmins();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up after tests
        $this->cleanUpAdmins();
    }

    private function cleanUpAdmins(): void
    {
        $admins = $this->userRepository->findAll();
        foreach ($admins as $admin) {
            if ($admin->hasRole(RoleEnum::ADMIN)) {
                $this->entityManager->remove($admin);
            }
        }
        $this->entityManager->flush();
    }

    public function testFailsWhenAdminAlreadyExists(): void
    {
        // Create an admin first
        $admin = new User();
        $admin->setFirstName('Existing')
            ->setLastName('Admin')
            ->setEmail('existing@admin.com')
            ->setUsername('existing.admin')
            ->setPhone('+33 6 00 00 00 00')
            ->setPassword($this->passwordHasher->hashPassword($admin, 'Test1234!'))
            ->addRole(RoleEnum::ADMIN);

        $this->entityManager->persist($admin);
        $this->entityManager->flush();

        // Try to create another admin - should fail without asking for input
        $this->commandTester->execute([]);

        // Assert the command failed
        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Un compte administrateur existe déjà', $output);
    }

    public function testCommandIsRegistered(): void
    {
        // Test that the command exists and is properly configured
        $application = new Application(self::$kernel);
        $command = $application->find('app:create:admin');
        
        $this->assertNotNull($command);
        $this->assertEquals('app:create:admin', $command->getName());
        $this->assertStringContainsString('administrateur', $command->getDescription());
    }
}
