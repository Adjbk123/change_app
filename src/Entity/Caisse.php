<?php

namespace App\Entity;

use App\Repository\CaisseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaisseRepository::class)]
class Caisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'caisses')]
    private ?Agence $agence = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, CompteCaisse>
     */
    #[ORM\OneToMany(targetEntity: CompteCaisse::class, mappedBy: 'caisse')]
    private Collection $compteCaisses;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    /**
     * @var Collection<int, ApproCaisse>
     */
    #[ORM\OneToMany(targetEntity: ApproCaisse::class, mappedBy: 'caisse')]
    private Collection $approCaisses;

    public function __construct()
    {
        $this->compteCaisses = new ArrayCollection();
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

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return Collection<int, CompteCaisse>
     */
    public function getCompteCaisses(): Collection
    {
        return $this->compteCaisses;
    }

    public function addCompteCaiss(CompteCaisse $compteCaiss): static
    {
        if (!$this->compteCaisses->contains($compteCaiss)) {
            $this->compteCaisses->add($compteCaiss);
            $compteCaiss->setCaisse($this);
        }

        return $this;
    }

    public function removeCompteCaiss(CompteCaisse $compteCaiss): static
    {
        if ($this->compteCaisses->removeElement($compteCaiss)) {
            // set the owning side to null (unless already changed)
            if ($compteCaiss->getCaisse() === $this) {
                $compteCaiss->setCaisse(null);
            }
        }

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

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
            $approCaiss->setCaisse($this);
        }

        return $this;
    }

    public function removeApproCaiss(ApproCaisse $approCaiss): static
    {
        if ($this->approCaisses->removeElement($approCaiss)) {
            // set the owning side to null (unless already changed)
            if ($approCaiss->getCaisse() === $this) {
                $approCaiss->setCaisse(null);
            }
        }

        return $this;
    }
}
