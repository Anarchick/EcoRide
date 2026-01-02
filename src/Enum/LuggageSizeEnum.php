<?php
namespace App\Enum;

enum LuggageSizeEnum: string
{
    case NONE = 'none';
    case HAND = 'hand';
    case SMALL = 'small';
    case MEDIUM = 'medium';
    case LARGE = 'large';

    public function ordinal(): int
    {
        return match($this) {
            self::NONE => 0,
            self::HAND => 1,
            self::SMALL => 2,
            self::MEDIUM => 3,
            self::LARGE => 4,
        };
    }

    public function getLabel(): string
    {
        return match($this) {
            self::NONE => 'Aucun bagage',
            self::HAND => 'Bagage à main',
            self::SMALL => 'Petit bagage',
            self::MEDIUM => 'Moyen bagage',
            self::LARGE => 'Grand bagage',
        };
    }

    public static function fromOrdinal(int $ordinal): self
    {
        return match($ordinal) {
            0 => self::NONE,
            1 => self::HAND,
            2 => self::SMALL,
            3 => self::MEDIUM,
            4 => self::LARGE,
            default => throw new \InvalidArgumentException("Invalid ordinal for LuggageSizeEnum: $ordinal"),
        };
    }
    
    public static function getChoices(): array
    {
        $choices = [];

        foreach (self::cases() as $case) {
            $choices[$case->getLabel()] = $case->ordinal();
        }

        return $choices;
    }

}
