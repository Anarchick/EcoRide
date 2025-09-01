<?php

namespace App\Tests;

use App\Repository\UserRepository;
use App\Tests\Utils\TestUtils;
use App\Validator\Constraints\UniqueEmail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\Container;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private Container $container;
    private UserRepository $userRepository;
    private EntityManagerInterface $em;
    private TestUtils $testUtils;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->container = static::getContainer();
        $this->testUtils = new TestUtils($this->container);
        $this->userRepository = $this->testUtils->getUserRepository();
        $this->em = $this->testUtils->getEntityManager();

        $this->testUtils->purgeDatabase();
    }

    private function submitForm(string $email): void
    {
        $this->client->submitForm("S'inscrire", [
            'registration[firstName]' => 'John',
            'registration[lastName]' => 'Doe',
            'registration[username]' => 'johndoe',
            'registration[phone]' => '+33612345678',
            'registration[email]' => $email,
            'registration[plainPassword][first]' => '2]~4t.C6=pqN23',
            'registration[plainPassword][second]' => '2]~4t.C6=pqN23',
            'registration[agreeTerms]' => true,
        ]);
    }

    public function testValidRegister(): void
    {
        // Register a new user
        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Inscription');

        $uniqueEmail = uniqid() . '@test.com';

        $this->submitForm($uniqueEmail);
        $this->assertResponseRedirects('/login');

        // Vérifier que l'utilisateur a bien été créé en base de données
        $user = $this->userRepository->findOneByEmail($uniqueEmail);
        $this->assertNotNull($user);
        $this->assertEquals('+33 6 12 34 56 78', $user->getPhone());
    }

    public function testRegisterWithExistingEmail(): void
    {
        $this->assertCount(0, $this->userRepository->findAll(), 'No user should exist before registration');
        
        $email = 'test@exemple.com';
        $user = $this->testUtils->createUser(email: $email);
        $this->em->persist($user);
        $this->em->flush();

        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->submitForm($email);
        $this->assertSelectorExists('#registration_email.is-invalid');
        $this->assertSelectorTextContains('.invalid-feedback', UniqueEmail::DEFAULT_MESSAGE);
        $this->assertCount(1, $this->userRepository->findAll(), 'Only one user should exist with this email');
    }
}