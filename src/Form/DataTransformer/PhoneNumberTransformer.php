<?php
namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class PhoneNumberTransformer implements DataTransformerInterface
{
    public function transform($value): string
    {
        if (null === $value || '' === $value) {
            return '';
        }

        // If already in correct format, return as is
        if (preg_match('/^\+33 [67](?: \d{2}){4}$/', $value)) {
            return $value;
        }

        try {
            return $this->reverseTransform($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    /**
     * Transform from form input to database format (+33 6 12 34 56 78)
     */
    public function reverseTransform($value): ?string
    {
        if (!$value) {
            return null;
        }

        // Remove all spaces, dots, and dashes
        $cleaned = preg_replace('/[\s\.-]/', '', $value);
        
        // Handle different input formats
        if (preg_match('/^(?:\+|00)?33([67]\d{8})$/', $cleaned, $matches)) {
            // +33, 0033, or 33 prefix
            $number = $matches[1];
        } elseif (preg_match('/^0([67]\d{8})$/', $cleaned, $matches)) {
            // French format starting with 06 or 07
            $number = $matches[1];
        } else {
            throw new TransformationFailedException('Invalid phone number format');
        }

        // Format to +33 6 12 34 56 78
        return '+33 ' .
            substr($number, 0, 1) . ' ' . 
            substr($number, 1, 2) . ' ' . 
            substr($number, 3, 2) . ' ' . 
            substr($number, 5, 2) . ' ' . 
            substr($number, 7, 2);
    }
}