<?php

namespace App\Entity;

use App\Repository\AttendanceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AttendanceRepository::class)]
class Attendance extends BaseEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime', nullable: false)]
    private ?\DateTime $clockIn = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $clockOut = null;

    #[ORM\ManyToOne(inversedBy: 'attendances')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClockIn(): ?\DateTimeInterface
    {
        return $this->clockIn;
    }

    public function setClockIn(\DateTimeInterface $clockIn): static
    {
        $this->clockIn = $clockIn;

        return $this;
    }

    public function getClockOut(): ?\DateTimeInterface
    {
        return $this->clockOut;
    }

    public function setClockOut(?\DateTimeInterface $clockOut): static
    {
        $this->clockOut = $clockOut;

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
}
