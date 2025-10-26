<?php

namespace App\Entity;

use App\Enum\RoleEnum;
use App\Enum\TravelStateEnum;
use App\Repository\TravelRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'travels')]
#[ORM\Index(name: 'idx_search_criteria', columns: ['departure', 'arrival', 'date', 'passengers_max'])]
#[ORM\Entity(repositoryClass: TravelRepository::class)]
class Travel
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $uuid;

    #[ORM\ManyToOne(inversedBy: 'travels')]
    #[ORM\JoinColumn(nullable: false, name: 'driver_uuid', referencedColumnName: 'uuid', onDelete: 'CASCADE')]
    private ?User $driver = null;

    #[ORM\ManyToOne(inversedBy: 'travels')]
    #[ORM\JoinColumn(nullable: false, name: 'car_uuid', referencedColumnName: 'uuid', onDelete: 'CASCADE')]
    private ?Car $car = null;

    #[ORM\Column(length: 90)] // No index Needed
    private ?string $departure = null;

    #[ORM\Column(length: 90, index: true)] // Index for stats
    private ?string $arrival = null;

    #[ORM\Column(index: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $date = null;

    #[ORM\Column]
    #[Assert\Positive()]
    private ?int $duration = null;

    #[ORM\Column]
    #[Assert\Positive()]
    private ?int $distance = null;

    #[ORM\Column(type: Types::SMALLINT)] // No index Needed
    #[Assert\Range(
        min: 1,
        max: 8
    )]
    private ?int $passengersMax = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero()]
    private ?int $cost = null;

    #[ORM\Column(enumType: TravelStateEnum::class, index: true)]
    private ?TravelStateEnum $state = TravelStateEnum::PENDING;

    #[ORM\OneToOne(mappedBy: 'travel', cascade: ['persist', 'remove'])]
    private ?TravelPreference $travelPreference = null;

    /**
     * @var Collection<int, Carpooler>
     */
    #[ORM\OneToMany(targetEntity: Carpooler::class, mappedBy: 'travel', orphanRemoval: true)]
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

    public function getDeparture(): ?string
    {
        return $this->departure;
    }

    public function setDeparture(string $departure): static
    {
        $this->departure = $departure;

        return $this;
    }

    public function getArrival(): ?string
    {
        return $this->arrival;
    }

    public function setArrival(string $arrival): static
    {
        $this->arrival = $arrival;

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
     * @return Collection<int, Carpooler>
     */
    public function getCarpoolers(): Collection
    {
        return $this->carpoolers;
    }

    public function addCarpooler(Carpooler $carpooler): static
    {
        if (!$this->carpoolers->contains($carpooler)) {
            $this->carpoolers->add($carpooler);
            $carpooler->setTravel($this);
        }
        
        return $this;
    }

    public function removeCarpooler(Carpooler $carpooler): static
    {
        if ($this->carpoolers->removeElement($carpooler)) {
            // set the owning side to null (unless already changed)
            if ($carpooler->getTravel() === $this) {
                $carpooler->setTravel(null);
            }
        }

        return $this;
    }

    // LOGIC METHODS

    public function getUsedSlots(): int
    {
        $usedSlots = 0;
        foreach ($this->carpoolers as $carpooler) {
            $usedSlots += $carpooler->getSlots();
        }
        return $usedSlots;
    }
    
    public function getAvailableSlots(): int
    {
        return $this->passengersMax - $this->getUsedSlots();
    }

    /**
     * Validate and return a slot count between 1 and the number of available places.
     * If there are no available places, return 1.
     * @param int $slot The requested slot count
     * @return int The validated slot count
     */
    public function getValidatedSlotCount(int $slot): int
    {
        $availablePlaces = $this->getAvailableSlots();
        return max(1, min((int)$slot, $availablePlaces));
    }

    public function isCarpooler(User $user): bool
    {
        foreach ($this->carpoolers as $carpooler) {
            if ($carpooler->getUser()->getUuid() === $user->getUuid()) {
                return true;
            }
        }
        return false;
    }

    public function join(User $user, int $slot, int $cost): Carpooler|null
    {
        if (!$this->isCarpooler($user)) {

            if ($this->state !== TravelStateEnum::PENDING) {
                throw new \InvalidArgumentException('Le trajet n\'est plus disponible pour la réservation.');
            }

            if ($this->isCarpooler($user)) {
                throw new \InvalidArgumentException('L\'utilisateur est déjà covoitureur de ce trajet.');
            }

            $validatedSlot = $this->getValidatedSlotCount($slot);
            if ($validatedSlot < $slot) {
                throw new \InvalidArgumentException('Le nombre de places demandées dépasse le nombre de places disponibles.');
            }

            if ($validatedSlot <= 0) {
                throw new \InvalidArgumentException('Le nombre de places réservées doit être au moins de 1.');
            }

            if ($user->getCredits() < $cost) {
                throw new \InvalidArgumentException('L\'utilisateur n\'a pas assez de crédits pour réserver ce trajet.');
            }

            if ($user->getUuid() === $this->driver?->getUuid()) {
                throw new \InvalidArgumentException('Le conducteur ne peut pas être covoitureur de son propre trajet.');
            }

            if ($user->isModerator()) {
                throw new \InvalidArgumentException('Un administrateur ou modérateur ne peut pas être covoitureur.');
            }

            $carpooler = new Carpooler();
            $carpooler->setTravel($this)
                ->setUser($user)
                ->setSlots($slot)
                ->setCost($cost);
            $this->addCarpooler($carpooler);

            $user->setCredits($user->getCredits() - $cost);

            return $carpooler;
        }

        return null;
    }

}
