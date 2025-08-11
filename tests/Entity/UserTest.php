<?php
namespace App\Tests\Entity;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class UserTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    public function testUuidIsGeneratedAndPersisted(): void
    {
        $user = new User();
        $user->setName('Test')
            ->setLastName('User')
            ->setUsername('testuser')
            ->setEmail('test@example.com')
            ->setPhone('0123456789')
            ->setPassword('password');

        $this->em->persist($user);
        $this->em->flush();
        $this->em->clear();

        // Vérifie que l'UUID est généré
        $this->assertNotNull($user->getUuid(), 'UUID should be generated');
        $this->assertInstanceOf(Uuid::class, $user->getUuid(), 'UUID should be instance of Uuid');
        $this->assertTrue(Uuid::isValid($user->getUuid()->toRfc4122()), 'UUID format should be valid');

        // Récupère l'utilisateur depuis la BDD
        $repo = $this->em->getRepository(User::class);
        $found = $repo->findOneBy(['uuid' => $user->getUuid()]);
        $this->assertNotNull($found, 'User should be found by UUID');
        $this->assertEquals($user->getUuid(), $found->getUuid(), 'UUID should match');
    }
}
