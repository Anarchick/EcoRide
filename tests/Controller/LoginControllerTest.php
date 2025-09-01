<?php

namespace App\Tests;

use App\Entity\User;
use App\Tests\Utils\TestUtils;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LoginControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private TestUtils $testUtils;
    private string $email = 'email@example.com';
    private string $password = '2]~4t.C6=pqN23';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->testUtils = new TestUtils($container);
        $em = $this->testUtils->getEntityManager();

        $this->testUtils->purgeDatabase();
        // Create a User fixture
        $user = $this->testUtils->createUser(email: $this->email, password: $this->password);
        $em->persist($user);
        $em->flush();
    }

    private function submitForm(string $email, string $password): void
    {
        $this->client->submitForm('Se connecter', [
            'login[email]' => $email,
            'login[password]' => $password,
        ]);
    }

    public function testValidLogin(): void
    {
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $this->submitForm($this->email, $this->password);

        self::assertResponseRedirects('/travel');
        $this->client->followRedirect();

        self::assertSelectorNotExists('.alert-danger');
        self::assertResponseIsSuccessful();

        // Check the data of the connected user
        $tokenStorage = $this->testUtils->getSecurityTokenStorage();
        /** @var User $user */
        $user = $tokenStorage->getToken()->getUser();

        $this->assertEquals($this->email, $user->getEmail(), 'The email of the connected user should match');
        $this->assertContains('ROLE_USER', $user->getRoles(), 'The user should have the ROLE_USER role');
    }

    public function testInvalidCredentials(): void
    {
        // Denied - Can't login with invalid email address.
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $this->submitForm('doesNotExist@example.com', 'random-password');

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        // Ensure we do not reveal if the user exists or not.
        self::assertSelectorTextContains('.alert-danger', 'Bad credentials.');
    }

    public function testInvalidPassword(): void
    {
        $this->client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $this->submitForm($this->email, 'bad-password');

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        self::assertSelectorExists('.alert-danger');
    }
}
