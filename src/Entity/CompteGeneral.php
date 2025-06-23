<?php

namespace App\Entity;

use App\Repository\CompteGeneralRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteGeneralRepository::class)]
class CompteGeneral
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'compteGenerals')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $soldeInitial = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $soldeRestant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 3)]
    private ?string $seuilAlerte = null;

    /**
     * @var Collection<int, ApproAgence>
     */
    #[ORM\OneToMany(targetEntity: ApproAgence::class, mappedBy: 'compteGeneral')]
    private Collection $approAgences;

    /**
     * @var Collection<int, ApproCompteGeneral>
     */
    #[ORM\OneToMany(targetEntity: ApproCompteGeneral::class, mappedBy: 'compteGeneral')]
    private Collection $approCompteGenerals;

    public function __construct()
    {
        $this->approAgences = new ArrayCollection();
        $this->approCompteGenerals = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getSoldeInitial(): ?string
    {
        return $this->soldeInitial;
    }

    public function setSoldeInitial(string $soldeInitial): static
    {
        $this->soldeInitial = $soldeInitial;

        return $this;
    }

    public function getSoldeRestant(): ?string
    {
        return $this->soldeRestant;
    }

    public function setSoldeRestant(string $soldeRestant): static
    {
        $this->soldeRestant = $soldeRestant;

        return $this;
    }

    public function getSeuilAlerte(): ?string
    {
        return $this->seuilAlerte;
    }

    public function setSeuilAlerte(string $seuilAlerte): static
    {
        $this->seuilAlerte = $seuilAlerte;

        return $this;
    }

    /**
     * @return Collection<int, ApproAgence>
     */
    public function getApproAgences(): Collection
    {
        return $this->approAgences;
    }

    public function addApproAgence(ApproAgence $approAgence): static
    {
        if (!$this->approAgences->contains($approAgence)) {
            $this->approAgences->add($approAgence);
            $approAgence->setCompteGeneral($this);
        }

        return $this;
    }

    public function removeApproAgence(ApproAgence $approAgence): static
    {
        if ($this->approAgences->removeElement($approAgence)) {
            // set the owning side to null (unless already changed)
            if ($approAgence->getCompteGeneral() === $this) {
                $approAgence->setCompteGeneral(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApproCompteGeneral>
     */
    public function getApproCompteGenerals(): Collection
    {
        return $this->approCompteGenerals;
    }

    public function addApproCompteGeneral(ApproCompteGeneral $approCompteGeneral): static
    {
        if (!$this->approCompteGenerals->contains($approCompteGeneral)) {
            $this->approCompteGenerals->add($approCompteGeneral);
            $approCompteGeneral->setCompteGeneral($this);
        }

        return $this;
    }

    public function removeApproCompteGeneral(ApproCompteGeneral $approCompteGeneral): static
    {
        if ($this->approCompteGenerals->removeElement($approCompteGeneral)) {
            // set the owning side to null (unless already changed)
            if ($approCompteGeneral->getCompteGeneral() === $this) {
                $approCompteGeneral->setCompteGeneral(null);
            }
        }

        return $this;
    }
}
