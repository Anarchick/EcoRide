<?php
namespace App\Tests\Security;

use SpecShaper\EncryptBundle\Encryptors\AesCbcEncryptor;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EncryptTest extends KernelTestCase
{
    private AesCbcEncryptor $encryptor;
    private const TEST_KEY = 'YBmNcBGfrZoayB+V254wdYa/abvxSUWJsjCtlMc1tRI=';
    private string $email = 'test@example.com';

    protected function setUp(): void
    {
        self::bootKernel();
        $this->encryptor = new AesCbcEncryptor(new EventDispatcher());
        $this->encryptor->setSecretKey(self::TEST_KEY);
    }

    public function testEncrypt(): void
    {
        $encrypted1 = $this->encryptor->encrypt($this->email);
        $encrypted2 = $this->encryptor->encrypt($this->email);

        $this->assertNotEquals($this->email, $encrypted1, 'Email should be encrypted');
        $this->assertNotEquals($encrypted1, $encrypted2, 'Encryption should produce different results each time');
    }

    public function testDecrypt(): void
    {
        $encrypted = $this->encryptor->encrypt($this->email);
        $decrypted = $this->encryptor->decrypt($encrypted);

        $this->assertEquals($this->email, $decrypted, 'Decrypted email should match original email');
    }
}