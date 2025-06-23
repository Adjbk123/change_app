<?php

namespace App\Entity;

use App\Repository\AgenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgenceRepository::class)]
class Agence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'agences')]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $ville = null;

    #[ORM\Column(length: 255)]
    private ?string $adresse = null;

    #[ORM\ManyToOne(inversedBy: 'agences')]
    private ?Pays $pays = null;

    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone2 = null;

    /**
     * @var Collection<int, AffectationAgence>
     */
    #[ORM\OneToMany(targetEntity: AffectationAgence::class, mappedBy: 'agence')]
    private Collection $affectationAgences;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'agence')]
    private Collection $users;

    #[ORM\ManyToOne(inversedBy: 'agences')]
    private ?User $responsable = null;

    /**
     * @var Collection<int, Caisse>
     */
    #[ORM\OneToMany(targetEntity: Caisse::class, mappedBy: 'agence')]
    private Collection $caisses;

    /**
     * @var Collection<int, CompteAgence>
     */
    #[ORM\OneToMany(targetEntity: CompteAgence::class, mappedBy: 'agence')]
    private Collection $compteAgences;

    /**
     * @var Collection<int, ApproAgence>
     */
    #[ORM\OneToMany(targetEntity: ApproAgence::class, mappedBy: 'agence')]
    private Collection $approAgences;

    /**
     * @var Collection<int, TauxChange>
     */
    #[ORM\OneToMany(targetEntity: TauxChange::class, mappedBy: 'agence')]
    private Collection $tauxChanges;

    public function __construct()
    {
        $this->affectationAgences = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->caisses = new ArrayCollection();
        $this->compteAgences = new ArrayCollection();
        $this->approAgences = new ArrayCollection();
        $this->tauxChanges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): static
    {
        $this->entreprise = $entreprise;

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

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
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

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getTelephone2(): ?string
    {
        return $this->telephone2;
    }

    public function setTelephone2(?string $telephone2): static
    {
        $this->telephone2 = $telephone2;

        return $this;
    }

    /**
     * @return Collection<int, AffectationAgence>
     */
    public function getAffectationAgences(): Collection
    {
        return $this->affectationAgences;
    }

    public function addAffectationAgence(AffectationAgence $affectationAgence): static
    {
        if (!$this->affectationAgences->contains($affectationAgence)) {
            $this->affectationAgences->add($affectationAgence);
            $affectationAgence->setAgence($this);
        }

        return $this;
    }

    public function removeAffectationAgence(AffectationAgence $affectationAgence): static
    {
        if ($this->affectationAgences->removeElement($affectationAgence)) {
            // set the owning side to null (unless already changed)
            if ($affectationAgence->getAgence() === $this) {
                $affectationAgence->setAgence(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setAgence($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getAgence() === $this) {
                $user->setAgence(null);
            }
        }

        return $this;
    }

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    /**
     * @return Collection<int, Caisse>
     */
    public function getCaisses(): Collection
    {
        return $this->caisses;
    }

    public function addCaiss(Caisse $caiss): static
    {
        if (!$this->caisses->contains($caiss)) {
            $this->caisses->add($caiss);
            $caiss->setAgence($this);
        }

        return $this;
    }

    public function removeCaiss(Caisse $caiss): static
    {
        if ($this->caisses->removeElement($caiss)) {
            // set the owning side to null (unless already changed)
            if ($caiss->getAgence() === $this) {
                $caiss->setAgence(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompteAgence>
     */
    public function getCompteAgences(): Collection
    {
        return $this->compteAgences;
    }

    public function addCompteAgence(CompteAgence $compteAgence): static
    {
        if (!$this->compteAgences->contains($compteAgence)) {
            $this->compteAgences->add($compteAgence);
            $compteAgence->setAgence($this);
        }

        return $this;
    }

    public function removeCompteAgence(CompteAgence $compteAgence): static
    {
        if ($this->compteAgences->removeElement($compteAgence)) {
            // set the owning side to null (unless already changed)
            if ($compteAgence->getAgence() === $this) {
                $compteAgence->setAgence(null);
            }
        }

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
            $approAgence->setAgence($this);
        }

        return $this;
    }

    public function removeApproAgence(ApproAgence $approAgence): static
    {
        if ($this->approAgences->removeElement($approAgence)) {
            // set the owning side to null (unless already changed)
            if ($approAgence->getAgence() === $this) {
                $approAgence->setAgence(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TauxChange>
     */
    public function getTauxChanges(): Collection
    {
        return $this->tauxChanges;
    }

    public function addTauxChange(TauxChange $tauxChange): static
    {
        if (!$this->tauxChanges->contains($tauxChange)) {
            $this->tauxChanges->add($tauxChange);
            $tauxChange->setAgence($this);
        }

        return $this;
    }

    public function removeTauxChange(TauxChange $tauxChange): static
    {
        if ($this->tauxChanges->removeElement($tauxChange)) {
            // set the owning side to null (unless already changed)
            if ($tauxChange->getAgence() === $this) {
                $tauxChange->setAgence(null);
            }
        }

        return $this;
    }
}
