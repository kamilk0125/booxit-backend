<?php

namespace App\Entity;

use App\Repository\ScheduleRepository;
use App\Service\GetterHelper\Attribute\Getter;
use App\Service\SetterHelper\Attribute\Setter;
use App\Service\SetterHelper\Task\OrganizationTask;
use App\Service\SetterHelper\Task\ScheduleAssignmentsTask;
use App\Service\SetterHelper\Task\ServicesTask;
use App\Service\SetterHelper\Task\WorkingHoursTask;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'schedules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToMany(targetEntity: Service::class, inversedBy: 'schedules')]
    private Collection $services;

    #[Assert\Regex(
        pattern: '/^(?!\s)[^<>]{6,40}$/i',
        message: 'Name must be from 6 to 40 characters long, cannot start from whitespace and contain characters: <>'
    )]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Assert\Length(
        max: 500,
        maxMessage: 'Max length of description is 500 characters'
    )]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'schedule', targetEntity: WorkingHours::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $workingHours;

    #[ORM\OneToMany(mappedBy: 'schedule', targetEntity: ScheduleAssignment::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $assignments;

    #[ORM\OneToMany(mappedBy: 'schedule', targetEntity: Reservation::class, orphanRemoval: true)]
    private Collection $reservations;

    #[ORM\OneToMany(mappedBy: 'schedule', targetEntity: FreeTerm::class, orphanRemoval: true)]
    private Collection $freeTerms;

    public function __construct()
    {
        $this->services = new ArrayCollection();
        $this->workingHours = new ArrayCollection();
        $this->assignments = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->freeTerms = new ArrayCollection();
    }

    #[Getter(groups:['schedule'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    #[Getter(groups:['schedule'])]
    public function getOrganizationId(): int
    {
        return $this->organization->getId();
    }

    #[Setter(targetParameter: 'organization_id', setterTask: OrganizationTask::class, groups: ['initOnly'])]
    public function setOrganization(?Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    #[Getter(groups:['schedule'])]
    public function getName(): ?string
    {
        return $this->name;
    }

    #[Setter]
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    #[Getter(groups:['schedule'])]
    public function getDescription(): ?string
    {
        return $this->description;
    }

    #[Setter]
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    #[Getter(groups: ['schedule-services'])]
    /**
     * @return Collection<int, Service>
     */
    public function getServices(): Collection
    {
        return $this->services;
    }

    public function hasService(Service $service):bool
    {
        $serviceExists = $this->services->exists(function($key, $value) use ($service){
            return $value === $service;
        });
        return $serviceExists;
    }

    #[Setter(setterTask: ServicesTask::class, groups: ['schedule-services'])]
    public function setServices(Collection $services)
    {
        $this->services = $services;
    }

    public function addService(Service $service): self
    {
        if (!$this->services->contains($service)) {
            $this->services->add($service);
        }

        return $this;
    }

    public function removeService(Service $service): self
    {
        $this->services->removeElement($service);

        return $this;
    }

    public function clearServices(): self
    {
        $this->services->clear();

        return $this;
    }

    #[Getter(groups: ['schedule-workingHours'])]
    /**
     * @return Collection<int, WorkingHours>
     */
    public function getWorkingHours(): Collection
    {
        return $this->workingHours;
    }

    public function getDayWorkingHours(string $day): ?WorkingHours
    {
        return $this->workingHours->findFirst(function($key, $value) use ($day){
            return $value->getDay() === $day;
        });
    }

    #[Setter(setterTask: WorkingHoursTask::class, groups: ['workingHours'])]
    public function setWorkingHours(Collection $workingHours): self
    {
        $this->workingHours = $workingHours;
        return $this;
    }

    public function addWorkingHours(WorkingHours $workingHour): self
    {
        if (!$this->workingHours->contains($workingHour)) {
            $this->workingHours->add($workingHour);
            $workingHour->setSchedule($this);
        }

        return $this;
    }

    public function removeWorkingHours(WorkingHours $workingHour): self
    {
        if ($this->workingHours->removeElement($workingHour)) {
            // set the owning side to null (unless already changed)
            if ($workingHour->getSchedule() === $this) {
                $workingHour->setSchedule(null);
            }
        }

        return $this;
    }

    #[Getter(groups: ['schedule-assignments'])]
    /**
     * @return Collection<int, ScheduleAssignment>
     */
    public function getAssignments(): Collection
    {
        return $this->assignments;
    }

    public function addAssignment(ScheduleAssignment $assignment): self
    {
        if (!$this->assignments->contains($assignment)) {
            $this->assignments->add($assignment);
            $assignment->setSchedule($this);
        }

        return $this;
    }

    public function removeAssignment(ScheduleAssignment $assignment): self
    {
        if ($this->assignments->removeElement($assignment)) {
            // set the owning side to null (unless already changed)
            if ($assignment->getSchedule() === $this) {
                $assignment->setSchedule(null);
            }
        }

        return $this;
    }

    #[Setter(setterTask: ScheduleAssignmentsTask::class, groups: ['assignments'])]
    public function setAssignments(Collection $assignments): self
    {
        $this->assignments = $assignments;
        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): self
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setSchedule($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): self
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getSchedule() === $this) {
                $reservation->setSchedule(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FreeTerm>
     */
    public function getFreeTerms(): Collection
    {
        return $this->freeTerms;
    }

    public function getDateFreeTerms(string $date): Collection
    {
        return $this->freeTerms->filter(function($key, $element) use ($date){
            return $element->getDate() === $date;
        });
    }


    public function addFreeTerm(FreeTerm $freeTerm): self
    {
        if (!$this->freeTerms->contains($freeTerm)) {
            $this->freeTerms->add($freeTerm);
            $freeTerm->setSchedule($this);
        }

        return $this;
    }

    public function removeFreeTerm(FreeTerm $freeTerm): self
    {
        if ($this->freeTerms->removeElement($freeTerm)) {
            // set the owning side to null (unless already changed)
            if ($freeTerm->getSchedule() === $this) {
                $freeTerm->setSchedule(null);
            }
        }

        return $this;
    }
    

}