<?php

namespace App\Entity;

use App\Enum\LuggageSizeEnum;
use App\Repository\TravelPreferenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'travel_preferences')]
#[ORM\Entity(repositoryClass: TravelPreferenceRepository::class)]
class TravelPreference
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'travelPreference', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'uuid')]
    private ?Travel $travel = null;

    #[ORM\Column(index: true)]
    private ?bool $isSmokingAllowed = false;

    #[ORM\Column(index: true)]
    private ?bool $isPetsAllowed = false;

    #[ORM\Column(index: true)]
    private ?int $luggageSize = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function getTravel(): ?Travel
    {
        return $this->travel;
    }

    public function setTravel(Travel $travel): static
    {
        $this->travel = $travel;

        return $this;
    }

    public function isSmokingAllowed(): ?bool
    {
        return $this->isSmokingAllowed;
    }

    public function setIsSmokingAllowed(bool $isSmokingAllowed): static
    {
        $this->isSmokingAllowed = $isSmokingAllowed;

        return $this;
    }

    public function isPetsAllowed(): ?bool
    {
        return $this->isPetsAllowed;
    }

    public function setIsPetsAllowed(bool $isPetsAllowed): static
    {
        $this->isPetsAllowed = $isPetsAllowed;

        return $this;
    }

    public function getLuggageSize(): ?int
    {
        return $this->luggageSize;
    }

    public function setLuggageSize(int | LuggageSizeEnum $luggageSize): static
    {
        if ($luggageSize instanceof LuggageSizeEnum) {
            $luggageSize = $luggageSize->ordinal();
        }

        $this->luggageSize = $luggageSize;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }
}
