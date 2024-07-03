<?php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User extends BaseEntity implements UserInterface,PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $gender = null;

    #[ORM\Column(length: 255)]
    private ?string $designation = null;

    #[ORM\Column]
    private ?int $phone = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'users')]
    #[ORM\JoinColumn(name: 'team_id', referencedColumnName: 'id', nullable: true)]
    private ?Team $team = null;

    /**
     * @var Collection<int, Team>
     */
    #[ORM\OneToMany(targetEntity: Team::class, mappedBy: 'teamLead')]
    private Collection $leadTeams;

    /**
     * @var Collection<int, Role>
     */
    #[ORM\ManyToMany(targetEntity: Role::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_roles')]
    private Collection $roles;

    /**
     * @var Collection<int, LeaveRequest>
     */
    #[ORM\OneToMany(targetEntity: LeaveRequest::class, mappedBy: 'user')]
    private Collection $leaveRequests;

    #[ORM\OneToMany(targetEntity: Authentication::class, mappedBy: 'user')]
    private Collection $authentications;

    public function __construct()
    {
        $this->leadTeams = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->leaveRequests = new ArrayCollection();
        $this->authentications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;
        return $this;
    }

    public function getPhone(): ?int
    {
        return $this->phone;
    }

    public function setPhone(int $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;
        return $this;
    }

    /**
     * @return Collection<int, Team>
     */
    public function getLeadTeams(): Collection
    {
        return $this->leadTeams;
    }

    public function addLeadTeam(Team $team): static
    {
        if (!$this->leadTeams->contains($team)) {
            $this->leadTeams->add($team);
            $team->setTeamLead($this);
        }

        return $this;
    }

    public function removeLeadTeam(Team $team): static
    {
        if ($this->leadTeams->removeElement($team)) {
            if ($team->getTeamLead() === $this) {
                $team->setTeamLead(null);
            }
        }

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles->map(fn(Role $role) => $role->getName())->toArray();
    }

    public function addRole(Role $role): static
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->addUser($this);
        }

        return $this;
    }

    public function removeRole(Role $role): static
    {
        if ($this->roles->removeElement($role)) {
            $role->removeUser($this);
        }

        return $this;
    }

    /**
     * Get the team lead of this user's team.
     *
     * @return User|null
     */
    public function getTeamLeadOf(): ?User
    {
        // Return the team lead associated with this user's team
        return $this->team ? $this->team->getTeamLead() : null;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }


    public function setPassword(string $hashedPassword) : static
    {
        $this->password = $hashedPassword;
        return $this;
    }


    /**
     * Get leave requests associated with this user.
     *
     * @return Collection<int, LeaveRequest>
     */
    public function getLeaveRequests(): Collection
    {
        return $this->leaveRequests;
    }

    /**
     * @return Collection|Authentication[]
     */
    public function getAuthentications(): Collection
    {
        return $this->authentications;
    }

    public function addAuthentication(Authentication $authentication): self
    {
        if (!$this->authentications->contains($authentication)) {
            $this->authentications[] = $authentication;
            $authentication->setUser($this);
        }

        return $this;
    }

    public function removeAuthentication(Authentication $authentication): self
    {
        if ($this->authentications->removeElement($authentication)) {
            // set the owning side to null (unless already changed)
            if ($authentication->getUser() === $this) {
                $authentication->setUser(null);
            }
        }

        return $this;
    }

    public function getUsername(): string
    {
        return $this->email;
    }



    public function eraseCredentials(): void
    {

    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }
}
