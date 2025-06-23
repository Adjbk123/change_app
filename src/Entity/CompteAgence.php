<?php

namespace App\Entity;

use App\Repository\CompteAgenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteAgenceRepository::class)]
class CompteAgence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'compteAgences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(inversedBy: 'compteAgences')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 3)]
    private ?string $soldeInitial = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 3)]
    private ?string $soldeRestant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $sueilAlerte = null;

    /**
     * @var Collection<int, ApproAgence>
     */
    #[ORM\OneToMany(targetEntity: ApproAgence::class, mappedBy: 'compteAgence')]
    private Collection $approAgences;

    /**
     * @var Collection<int, ApproCaisse>
     */
    #[ORM\OneToMany(targetEntity: ApproCaisse::class, mappedBy: 'compteAgence')]
    private Collection $approCaisses;

    public function __construct()
    {
        $this->approAgences = new ArrayCollection();
        $this->approCaisses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgence(): ?Agence
    {
        return $this->agence;
    }

    public function setAgence(?Agence $agence): static
    {
        $this->agence = $agence;

        return $this;
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

    public function getSueilAlerte(): ?string
    {
        return $this->sueilAlerte;
    }

    public function setSueilAlerte(string $sueilAlerte): static
    {
        $this->sueilAlerte = $sueilAlerte;

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
            $approAgence->setCompteAgence($this);
        }

        return $this;
    }

    public function removeApproAgence(ApproAgence $approAgence): static
    {
        if ($this->approAgences->removeElement($approAgence)) {
            // set the owning side to null (unless already changed)
            if ($approAgence->getCompteAgence() === $this) {
                $approAgence->setCompteAgence(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApproCaisse>
     */
    public function getApproCaisses(): Collection
    {
        return $this->approCaisses;
    }

    public function addApproCaiss(ApproCaisse $approCaiss): static
    {
        if (!$this->approCaisses->contains($approCaiss)) {
            $this->approCaisses->add($approCaiss);
            $approCaiss->setCompteAgence($this);
        }

        return $this;
    }

    public function removeApproCaiss(ApproCaisse $approCaiss): static
    {
        if ($this->approCaisses->removeElement($approCaiss)) {
            // set the owning side to null (unless already changed)
            if ($approCaiss->getCompteAgence() === $this) {
                $approCaiss->setCompteAgence(null);
            }
        }

        return $this;
    }
}
