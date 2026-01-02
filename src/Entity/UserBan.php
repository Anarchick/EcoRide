<?php

namespace App\Entity;

use App\Repository\UserBanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use SpecShaper\EncryptBundle\Annotations\Encrypted;

#[ORM\Table(name: 'user_bans')]
#[ORM\Entity(repositoryClass: UserBanRepository::class)]
class UserBan
{
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'userBan', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: 'uuid')]
    private ?User $user = null;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createAt = null;

    #[Encrypted]
    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $reason = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }
}
