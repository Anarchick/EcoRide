<?php

use App\Tests\Utils\TestUtils;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

class UserRepositoryTest extends KernelTestCase
{
    private TestUtils $testUtils;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->testUtils = new TestUtils(static::getContainer());
        $this->em = $this->testUtils->getEntityManager();

        $this->testUtils->purgeDatabase();
    }



    public function testGetUserByUuid(): void
    {
        $user = $this->testUtils->createUser();
        $this->em->persist($user);
        $this->em->flush();

        $uuid = $user->getUuid();
        $uuidStr = $uuid->toRfc4122();
        $uuidStr32 = str_replace('-', '', $uuidStr);

        $userFound = $this->testUtils->getUserRepository()->getUserByUuid($uuidStr);

        $this->assertNotNull($userFound);
        $this->assertEquals($uuid, $userFound->getUuid());

        $userFound = $this->testUtils->getUserRepository()->getUserByUuid($uuidStr32);

        $this->assertNotNull($userFound);
        $this->assertEquals($uuid, $userFound->getUuid());
    }

}