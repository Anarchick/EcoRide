<?php

namespace App\Entity;

use App\Enum\TravelStateEnum;
use App\Repository\TravelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Table(name: 'travels',
    indexes: [
        new ORM\Index(
            name: 'idx_search_criteria',
            columns: ['origin', 'destination', 'date', 'passengers_max']
        )
    ]
)]
#[ORM\Entity(repositoryClass: TravelRepository::class)]
class Travel
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $uuid;

    #[ORM\ManyToOne(inversedBy: 'travels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $driver = null;

    #[ORM\ManyToOne(inversedBy: 'travels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Car $car = null;

    #[ORM\Column(length: 90)] // No index Needed
    private ?string $origin = null;

    #[ORM\Column(length: 90, index: true)] // Index for stats
    private ?string $destination = null;

    #[ORM\Column(index: true)] // Index for stats
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column]
    private ?int $distance = null;

    #[ORM\Column(type: Types::SMALLINT)] // No index Needed
    private ?int $passengersMax = null;

    #[ORM\Column]
    private ?int $cost = null;

    #[ORM\Column(enumType: TravelStateEnum::class, index: true)]
    private ?TravelStateEnum $state = TravelStateEnum::PENDING;

    #[ORM\OneToOne(mappedBy: 'travel', cascade: ['persist', 'remove'])]
    private ?TravelPreference $travelPreference = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'carpools')]
    private Collection $carpoolers;

    public function __construct()
    {
        $this->carpoolers = new ArrayCollection();
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getDriver(): ?User
    {
        return $this->driver;
    }

    public function setDriver(?User $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    public function getCar(): ?Car
    {
        return $this->car;
    }

    public function setCar(?Car $car): static
    {
        $this->car = $car;

        return $this;
    }

    public function getOrigin(): ?string
    {
        return $this->origin;
    }

    public function setOrigin(string $origin): static
    {
        $this->origin = $origin;

        return $this;
    }

    public function getDestination(): ?string
    {
        return $this->destination;
    }

    public function setDestination(string $destination): static
    {
        $this->destination = $destination;

        return $this;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getPassengersMax(): ?int
    {
        return $this->passengersMax;
    }

    public function setPassengersMax(int $passengersMax): static
    {
        $this->passengersMax = $passengersMax;

        return $this;
    }

    public function getCost(): ?int
    {
        return $this->cost;
    }

    public function setCost(int $cost): static
    {
        $this->cost = $cost;

        return $this;
    }

    public function getState(): ?TravelStateEnum
    {
        return $this->state;
    }

    public function setState(TravelStateEnum $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getTravelPreference(): ?TravelPreference
    {
        return $this->travelPreference;
    }

    public function setTravelPreference(TravelPreference $travelPreference): static
    {
        // set the owning side of the relation if necessary
        if ($travelPreference->getTravel() !== $this) {
            $travelPreference->setTravel($this);
        }

        $this->travelPreference = $travelPreference;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getCarpoolers(): Collection
    {
        return $this->carpoolers;
    }

    public function addCarpooler(User $carpooler): static
    {
        if (!$this->carpoolers->contains($carpooler)) {
            $this->carpoolers->add($carpooler);
            $carpooler->addCarpool($this);
        }

        return $this;
    }

    public function removeCarpooler(User $carpooler): static
    {
        if ($this->carpoolers->removeElement($carpooler)) {
            $carpooler->removeCarpool($this);
        }

        return $this;
    }
}
