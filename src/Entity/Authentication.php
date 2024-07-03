<?php

namespace App\Entity;

use App\Repository\AuthenticationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthenticationRepository::class)]
class Authentication extends BaseEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $signIn;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $signOut;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getSignIn(): \DateTimeInterface
    {
        return $this->signIn;
    }

    public function setSignIn(\DateTimeInterface $signIn): self
    {
        $this->signIn = $signIn;

        return $this;
    }

    public function getSignOut(): ?\DateTimeInterface
    {
        return $this->signOut;
    }

    public function setSignOut(?\DateTimeInterface $signOut): self
    {
        $this->signOut = $signOut;

        return $this;
    }
}
