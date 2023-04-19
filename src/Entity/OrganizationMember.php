<?php

namespace App\Entity;

use App\Repository\OrganizationMemberRepository;
use App\Service\GetterHelper\Attribute\Getter;
use App\Service\SetterHelper\Attribute\Setter;
use App\Service\SetterHelper\Task\MemberRoleTask;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizationMemberRepository::class)]
class OrganizationMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'organizationMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(inversedBy: 'organizationAssignments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $appUser = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $roles = [];

    #[ORM\OneToMany(mappedBy: 'organizationMember', targetEntity: ScheduleAssignment::class, orphanRemoval: true)]
    private Collection $scheduleAssignments;

    public function __construct()
    {
        $this->scheduleAssignments = new ArrayCollection();
    }

    #[Getter(groups: ['schedule-assignments'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    #[Getter(groups: ['schedule-assignments'], propertyNameAlias: 'user')]
    public function getAppUser(): ?User
    {
        return $this->appUser;
    }

    public function setAppUser(?User $appUser): self
    {
        $this->appUser = $appUser;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    #[Setter(setterTask: MemberRoleTask::class)]
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function hasRoles(array $roles):bool
    {
        return empty(array_diff($roles, $this->roles));
    }

    /**
     * @return Collection<int, ScheduleAssignment>
     */
    public function getScheduleAssignments(): Collection
    {
        return $this->scheduleAssignments;
    }

    public function addScheduleAssignment(ScheduleAssignment $scheduleAssignment): self
    {
        if (!$this->scheduleAssignments->contains($scheduleAssignment)) {
            $this->scheduleAssignments->add($scheduleAssignment);
            $scheduleAssignment->setOrganizationMember($this);
        }

        return $this;
    }

    public function removeScheduleAssignment(ScheduleAssignment $scheduleAssignment): self
    {
        if ($this->scheduleAssignments->removeElement($scheduleAssignment)) {
            // set the owning side to null (unless already changed)
            if ($scheduleAssignment->getOrganizationMember() === $this) {
                $scheduleAssignment->setOrganizationMember(null);
            }
        }

        return $this;
    }

   
}