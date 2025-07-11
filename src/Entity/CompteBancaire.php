<?php

namespace App\Entity;

use App\Repository\CompteBancaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CompteBancaireRepository::class)]
#[UniqueEntity(fields: ['numeroBancaire'], message: 'Un compte existe déjà avec cet email.')]
class CompteBancaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'compteBancaires')]
    private ?Pays $pays = null;

    #[ORM\ManyToOne(inversedBy: 'compteBancaires')]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $soldeInitial = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $soldeRestant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $seuilAlerte = null;

    #[ORM\ManyToOne(inversedBy: 'compteBancaires')]
    private ?Banque $banque = null;

    #[ORM\Column(length: 255)]
    private ?string $numeroBancaire = null;

    /**
     * @var Collection<int, ApproCompteBancaire>
     */
    #[ORM\OneToMany(targetEntity: ApproCompteBancaire::class, mappedBy: 'compteBancaire')]
    private Collection $approCompteBancaires;

    /**
     * @var Collection<int, MouvementCompteBancaire>
     */
    #[ORM\OneToMany(targetEntity: MouvementCompteBancaire::class, mappedBy: 'compteBancaire')]
    private Collection $mouvementCompteBancaires;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'compteBancaire')]
    private Collection $operations;

    public function __construct()
    {
        $this->approCompteBancaires = new ArrayCollection();
        $this->mouvementCompteBancaires = new ArrayCollection();
        $this->operations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPays(): ?Pays
    {
        return $this->pays;
    }

    public function setPays(?Pays $pays): static
    {
        $this->pays = $pays;

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

    public function getSeuilAlerte(): ?string
    {
        return $this->seuilAlerte;
    }

    public function setSeuilAlerte(string $seuilAlerte): static
    {
        $this->seuilAlerte = $seuilAlerte;

        return $this;
    }

    public function getBanque(): ?Banque
    {
        return $this->banque;
    }

    public function setBanque(?Banque $banque): static
    {
        $this->banque = $banque;

        return $this;
    }

    public function getNumeroBancaire(): ?string
    {
        return $this->numeroBancaire;
    }

    public function setNumeroBancaire(string $numeroBancaire): static
    {
        $this->numeroBancaire = $numeroBancaire;

        return $this;
    }

    /**
     * @return Collection<int, ApproCompteBancaire>
     */
    public function getApproCompteBancaires(): Collection
    {
        return $this->approCompteBancaires;
    }

    public function addApproCompteBancaire(ApproCompteBancaire $approCompteBancaire): static
    {
        if (!$this->approCompteBancaires->contains($approCompteBancaire)) {
            $this->approCompteBancaires->add($approCompteBancaire);
            $approCompteBancaire->setCompteBancaire($this);
        }

        return $this;
    }

    public function removeApproCompteBancaire(ApproCompteBancaire $approCompteBancaire): static
    {
        if ($this->approCompteBancaires->removeElement($approCompteBancaire)) {
            // set the owning side to null (unless already changed)
            if ($approCompteBancaire->getCompteBancaire() === $this) {
                $approCompteBancaire->setCompteBancaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MouvementCompteBancaire>
     */
    public function getMouvementCompteBancaires(): Collection
    {
        return $this->mouvementCompteBancaires;
    }

    public function addMouvementCompteBancaire(MouvementCompteBancaire $mouvementCompteBancaire): static
    {
        if (!$this->mouvementCompteBancaires->contains($mouvementCompteBancaire)) {
            $this->mouvementCompteBancaires->add($mouvementCompteBancaire);
            $mouvementCompteBancaire->setCompteBancaire($this);
        }

        return $this;
    }

    public function removeMouvementCompteBancaire(MouvementCompteBancaire $mouvementCompteBancaire): static
    {
        if ($this->mouvementCompteBancaires->removeElement($mouvementCompteBancaire)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCompteBancaire->getCompteBancaire() === $this) {
                $mouvementCompteBancaire->setCompteBancaire(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setCompteBancaire($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getCompteBancaire() === $this) {
                $operation->setCompteBancaire(null);
            }
        }

        return $this;
    }
}
