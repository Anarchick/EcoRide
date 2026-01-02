<?php

namespace App\Entity;

use App\Repository\CarpoolerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'Carpoolers')]
#[ORM\Entity(repositoryClass: CarpoolerRepository::class)]
class Carpooler
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'carpoolers')]
    #[ORM\JoinColumn(name: 'travel_uuid', referencedColumnName: 'uuid', nullable: false)]
    private ?Travel $travel = null;

    #[ORM\ManyToOne(inversedBy: 'carpoolers')]
    #[ORM\JoinColumn(name: 'user_uuid', referencedColumnName: 'uuid', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::SMALLINT)]
    private ?int $slots = null;

    #[ORM\Column]
    private ?int $cost = null;

    #[ORM\Column(index: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $bookedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTravel(): ?Travel
    {
        return $this->travel;
    }

    public function setTravel(?Travel $travel): static
    {
        $this->travel = $travel;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getSlots(): ?int
    {
        return $this->slots;
    }

    public function setSlots(int $slots): static
    {
        $this->slots = $slots;

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

    public function getBookedAt(): ?\DateTimeImmutable
    {
        return $this->bookedAt;
    }

    public function setBookedAt(\DateTimeImmutable $bookedAt): static
    {
        $this->bookedAt = $bookedAt;

        return $this;
    }
}
