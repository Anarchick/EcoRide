<?php
namespace App\Form\DataTransformer;

use App\Enum\LuggageSizeEnum;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class LuggageSizeTransformer implements DataTransformerInterface
{
    /**
     * Transform enum to ordinal integer
     */
    public function transform($value): ?int
    {
        if (null === $value) {
            return null;
        }

        if (!($value instanceof LuggageSizeEnum)) {
            throw new TransformationFailedException('Expected a LuggageSizeEnum.');
        }

        return $value->ordinal();
    }

    /**
     * Transform ordinal to enum
     */
    public function reverseTransform($value): ?LuggageSizeEnum
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if (!is_int($value)) {
            throw new TransformationFailedException('Expected an integer or numeric string.');
        }

        return LuggageSizeEnum::fromOrdinal($value);
    }
}