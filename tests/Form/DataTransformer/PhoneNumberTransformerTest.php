<?php
namespace App\Form\DataTransformer;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PhoneNumberTransformerTest extends KernelTestCase
{
    public static function dataProvider(): array
    {
        $expected = '+33 6 12 34 56 78';
        return [
            'first' => ['0612345678', $expected],
            'second' => ['00336.12.34.56.78', $expected],
            'third' => ['+33 6 12  34-5678', $expected]
        ];
    }

    #[DataProvider('dataProvider')]
    public function testPhoneNumber(string $value, string $expected): void
    {
        $transformer = new PhoneNumberTransformer();
        $result = $transformer->reverseTransform($value);
        $this->assertEquals($expected, $result);
    }

    public function testWrongPhoneNumber(): void
    {
        $transformer = new PhoneNumberTransformer();
        $this->expectException(TransformationFailedException::class);
        $transformer->reverseTransform('0123456789');
    }
}