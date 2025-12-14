<?php

namespace App\Entity;

use App\Enum\RoleEnum;
use App\Repository\UserRepository;
use App\Validator\Constraints\UniqueEmail;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SpecShaper\EncryptBundle\Annotations\Encrypted;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'users')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['uuid'], message: 'There is already an account with this uuid')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $uuid;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[Encrypted]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2, max: 20)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ \'\-]+$/', message: 'Votre nom ne doit contenir que des lettres, des espaces ou des tirets.')]
    #[ORM\Column(length: 255)]
    private ?string $firstName = null;

    #[Encrypted]
    #[Assert\NotBlank()]
    #[Assert\Length(min: 2, max: 25)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ \'\-]+$/', message: 'Votre nom ne doit contenir que des lettres, des espaces ou des tirets.')]
    #[ORM\Column(length: 255)]
    private ?string $lastName = null;

    #[Encrypted]
    #[Assert\NotBlank()]
    #[Assert\Length(min:3, max: 20)]
    #[Assert\Regex(pattern: '/^[a-zA-ZÀ-ÿ\d _\-]+$/', message: 'Votre pseudonyme ne doit contenir que des lettres, des chiffres, des tirets ou des underscores.')]
    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[Encrypted]
    #[Assert\NotBlank()]
    #[Assert\Length(max: 128)]
    #[UniqueEmail]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 64, unique: true, index: true)]
    private ?string $emailHash = null;

    #[Encrypted]
    #[Assert\NotBlank()]
    // #[Assert\Length(min: 10, max: 15)]
    #[Assert\Regex(pattern:'/^\+33 [67](?: \d{2}){4}$/', message: 'Votre numéro de téléphone portable doit être au format +33 6 12 34 56 78')]
    #[ORM\Column(length: 255, unique: true)]
    private ?string $phone = null;

    #[ORM\Column]
    private ?int $credits = 20; // 20 credits offer at the account creation

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column]
    private ?bool $isVerified = false;

    #[ORM\Column(type: Types::FLOAT, precision: 2, scale: 1, nullable: true)]
    private ?float $ratingAverage = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $avatarUrl = null;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'author', orphanRemoval: true)]
    private Collection $ownReviews;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'user')]
    private Collection $reviews;

    /**
     * @var Collection<int, Car>
     */
    #[ORM\ManyToMany(targetEntity: Car::class, mappedBy: 'user')]
    private Collection $cars;

    /**
     * @var Collection<int, Transaction>
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'user')]
    private Collection $transactions;

    /**
     * @var Collection<int, Travel>
     */
    #[ORM\OneToMany(targetEntity: Travel::class, mappedBy: 'driver', orphanRemoval: true)]
    private Collection $travels;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\OneToMany(targetEntity: Role::class, mappedBy: 'user', orphanRemoval: true, cascade: ['persist'])]
    private Collection $roles;

    /**
     * @var Collection<int, Carpooler>
     */
    #[ORM\OneToMany(targetEntity: Carpooler::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $carpoolers;

    public function __construct()
    {
        $this->ownReviews = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->cars = new ArrayCollection();
        $this->transactions = new ArrayCollection();
        $this->travels = new ArrayCollection();
        $this->roles = new ArrayCollection();
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

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->uuid->toString();
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        $this->setEmailHash(hash('sha256', $email));

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getCredits(): ?int
    {
        return $this->credits;
    }

    public function setCredits(int $credits): static
    {
        $this->credits = $credits;

        return $this;
    }

    public function addCredits(int $amount): static
    {
        $this->credits += $amount;

        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function isVerified(): ?bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function getRatingAverage(): ?float
    {
        return $this->ratingAverage;
    }

    public function setRatingAverage(?float $ratingAverage): static
    {
        $this->ratingAverage = $ratingAverage;

        return $this;
    }

    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    public function setAvatarUrl(?string $avatarUrl): static
    {
        $this->avatarUrl = $avatarUrl;

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getOwnReviews(): Collection
    {
        return $this->ownReviews;
    }

    public function addOwnReview(Review $ownReview): static
    {
        if (!$this->ownReviews->contains($ownReview)) {
            $this->ownReviews->add($ownReview);
            $ownReview->setAuthor($this);
        }

        return $this;
    }

    public function removeOwnReview(Review $ownReview): static
    {
        if ($this->ownReviews->removeElement($ownReview)) {
            // set the owning side to null (unless already changed)
            if ($ownReview->getAuthor() === $this) {
                $ownReview->setAuthor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setUser($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getUser() === $this) {
                $review->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Car>
     */
    public function getCars(): Collection
    {
        return $this->cars;
    }

    public function addCar(Car $car): static
    {
        if (!$this->cars->contains($car)) {
            $this->cars->add($car);
            $car->addUser($this);
        }

        return $this;
    }

    public function removeCar(Car $car): static
    {
        if ($this->cars->removeElement($car)) {
            $car->removeUser($this);
        }

        return $this;
    }

    /**
     * Check if user has at least one car (not removed)
     * @return bool
     */
    public function hasCar(): bool
    {
        foreach ($this->cars as $car) {
            if (!$car->isRemoved()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setUser($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getUser() === $this) {
                $transaction->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Travel>
     */
    public function getTravels(): Collection
    {
        return $this->travels;
    }

    public function addTravel(Travel $travel): static
    {
        if (!$this->travels->contains($travel)) {
            $this->travels->add($travel);
            $travel->setDriver($this);
        }

        return $this;
    }

    public function removeTravel(Travel $travel): static
    {
        if ($this->travels->removeElement($travel)) {
            // set the owning side to null (unless already changed)
            if ($travel->getDriver() === $this) {
                $travel->setDriver(null);
            }
        }

        return $this;
    }

    /**
     * @see Symfony\Component\Security\Core\User\UserInterface
     */
    public function getRoles(): array
    {
        $roles = array_map(
            fn($roleEntity) => $roleEntity->getRole()->value,
            $this->roles->toArray()
        );

        // guarantee every user at least has ROLE_USER
        $roles[] = RoleEnum::USER->value;

        return array_unique($roles);
    }

    public function addRole(Role|RoleEnum $role): static
    {
        if ($role instanceof RoleEnum) {

            foreach ($this->roles as $roleEntity) {
                if ($roleEntity->getRole() === $role) {
                    return $this;
                }
            }

            $roleEntity = (new Role())->setRole($role)->setUser($this);
            $this->roles->add($roleEntity);
        } else { // $role is a Role entity
            if (!$this->roles->contains($role)) {
                $role->setUser($this);
                $this->roles->add($role);
            }
        }

        return $this;
    }

    public function removeRole(Role|RoleEnum $role): static
    {
        if ($role instanceof RoleEnum) {
            // https://github.com/doctrine/collections/issues/467
            // Filter does not trigger Doctrine events
            // $this->roles->filter(
            //     fn($roleEntity) => $roleEntity->getRole() !== $role
            // );
            $rolesToRemove = [];
            foreach ($this->roles as $roleEntity) {
                if ($roleEntity->getRole() === $role) {
                    $rolesToRemove[] = $roleEntity;
                }
            }
            
            // Remove each role properly to trigger Doctrine cascades
            foreach ($rolesToRemove as $roleToRemove) {
                $this->removeRole($roleToRemove);
            }

        } else {
            $this->roles->removeElement($role);
        }

        return $this;
    }

    public function getEmailHash(): ?string
    {
        return $this->emailHash;
    }

    public function setEmailHash(string $emailHash): static
    {
        $this->emailHash = $emailHash;

        return $this;
    }

    // LOGIC METHODS

    public function hasRole(RoleEnum $role): bool
    {
        foreach ($this->roles as $roleEntity) {
            if ($roleEntity->getRole() === $role) {
                return true;
            }
        }

        return false;
    }

    public function isModerator(): bool
    {
        return $this->hasRole(RoleEnum::MODERATOR) || $this->hasRole(RoleEnum::ADMIN);
    }

    public function isDriver(): bool
    {
        return $this->hasRole(RoleEnum::DRIVER);
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
            $carpooler->setUser($this);
        }

        return $this;
    }

    public function removeCarpooler(Carpooler $carpooler): static
    {
        if ($this->carpoolers->removeElement($carpooler)) {
            // set the owning side to null (unless already changed)
            if ($carpooler->getUser() === $this) {
                $carpooler->setUser(null);
            }
        }

        return $this;
    }
}
