<?php
namespace App\Tests\Repository;

use App\Entity\User;
use App\Tests\Utils\TestUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

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

        /** @var User $userFound */
        $userFound = $this->testUtils->getUserRepository()->getByUuid($uuidStr);

        $this->assertNotNull($userFound);
        $this->assertEquals($uuid, $userFound->getUuid());

        /** @var User $userFound */
        $userFound = $this->testUtils->getUserRepository()->getByUuid($uuidStr32);

        $this->assertNotNull($userFound);
        $this->assertEquals($uuid, $userFound->getUuid());
    }

}