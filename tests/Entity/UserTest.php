<?php
namespace App\Tests\Entity;

use App\Entity\User;
use App\Enum\RoleEnum;
use App\Repository\UserRepository;
use App\Tests\Utils\TestUtils;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Uid\Uuid;

class UserTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private TestUtils $testUtils;

    private function fakeUser(): User
    {
        $email = uniqid() . '@test.com';
        $phone = '+336' . sprintf('%09d', \random_int(0, 999999999));
        $user = $this->testUtils->createUser(email: $email, phone: $phone, firstName: 'Test');

        $this->em->persist($user);
        $this->em->flush(); // The UUID will be generated during the flush

        return $user;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $this->testUtils = new TestUtils(static::getContainer());
        $this->em = $this->testUtils->getEntityManager();
        $this->testUtils->purgeDatabase();

        $this->userRepository = $this->testUtils->getUserRepository();
    }

    public function testUuidIsRandomlyGenerated(): void
    {
        $user1 = $this->fakeUser();
        $user2 = $this->fakeUser();

        $this->assertNotNull($user1->getUuid(), 'UUID should be generated');
        $this->assertInstanceOf(Uuid::class, $user1->getUuid(), 'UUID should be instance of Uuid');
        $this->assertTrue(Uuid::isValid($user1->getUuid()->toRfc4122()), 'UUID format should be valid');
        $this->assertNotEquals($user1->getUuid(), $user2->getUuid());
    }

    public function testUuidIsPersisted(): void
    {
        $user = $this->fakeUser();
        $found = $this->userRepository->getUserByUuid($user->getUuid());

        $this->assertNotNull($found, 'User should be found by UUID');
        $this->assertEquals($user->getUuid(), $found->getUuid(), 'UUID should match');
    }

    public function testIsGranted(): void
    {
        $user = $this->fakeUser();
        $user->addRole(RoleEnum::ADMIN->toEntity($user));
        $this->em->flush();

        /** @var User $user */
        $user = $this->userRepository->getUserByUuid($user->getUuid());

        // Checks that the user has the ADMIN role from BDD
        $this->assertContains(RoleEnum::ADMIN->value, $user->getRoles(), 'User should have ROLE_ADMIN');
        
        // To test the role hierarchy with AuthorizationChecker, we need to create a security token
        /** @var TokenStorageInterface $tokenStorage */
        $tokenStorage = $this->testUtils->getSecurityTokenStorage();
        $token = new UsernamePasswordToken(
            $user,
            'main',
            $user->getRoles()
        );
        $tokenStorage->setToken($token);
        
        /** @var AuthorizationCheckerInterface $security */
        $security = static::getContainer()->get('security.authorization_checker');
        $this->assertTrue($security->isGranted(RoleEnum::MODERATOR->value), 'User should have ROLE_MODERATOR granted due to role hierarchy');
    }

    public function testEncrypted(): void
    {
        $user = $this->fakeUser();
        $found = $this->userRepository->getUserByUuid($user->getUuid());

        $this->assertEquals($user->getFirstName(), 'Test', 'Name should match before decryption');
        $this->assertEquals($user->getFirstName(), $found->getFirstName(), 'Name should match after decryption');

        // Check if encrypted field in DB
        $result = $this->em->createQueryBuilder()
            ->select('u.firstName')
            ->from(User::class, 'u')
            ->where('u.uuid = :uuid')
            ->setParameter('uuid', $user->getUuid())
            ->getQuery()
            ->getSingleScalarResult();
            
        $this->assertNotEquals($user->getFirstName(), $result, 'Name should be encrypted in the database');
    }
}
