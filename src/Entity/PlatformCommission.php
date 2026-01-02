<?php

namespace App\Entity;

use App\Repository\PlatformCommissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: 'platform_commissions')]
#[ORM\UniqueConstraint(name: 'unique_carpooler_per_travel', columns: ['carpooler', 'travel'])]
#[ORM\Entity(repositoryClass: PlatformCommissionRepository::class)]
class PlatformCommission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createAt = null;

    #[ORM\ManyToOne(inversedBy: 'platformCommission')]
    #[ORM\JoinColumn(name:'travel', referencedColumnName: 'uuid', nullable: false)]
    private ?Travel $travel = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name:'carpooler', nullable: false)]
    private ?Carpooler $carpooler = null;

    #[ORM\Column]
    private ?int $credits = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCreateAt(): ?\DateTimeImmutable
    {
        return $this->createAt;
    }

    public function setCreateAt(\DateTimeImmutable $createAt): static
    {
        $this->createAt = $createAt;

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

    public function getTravel(): ?Travel
    {
        return $this->travel;
    }

    public function setTravel(?Travel $travel): static
    {
        $this->travel = $travel;

        return $this;
    }

    public function getCarpooler(): ?Carpooler
    {
        return $this->carpooler;
    }

    public function setCarpooler(Carpooler $carpooler): static
    {
        $this->carpooler = $carpooler;

        return $this;
    }
}
