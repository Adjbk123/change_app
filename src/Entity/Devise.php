<?php

namespace App\Entity;

use App\Repository\DeviseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeviseRepository::class)]
class Devise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $codeIso = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $symbole = null;

    #[ORM\Column]
    private ?bool $isActive = null;

    /**
     * @var Collection<int, CompteCaisse>
     */
    #[ORM\OneToMany(targetEntity: CompteCaisse::class, mappedBy: 'devise')]
    private Collection $compteCaisses;

    /**
     * @var Collection<int, CompteAgence>
     */
    #[ORM\OneToMany(targetEntity: CompteAgence::class, mappedBy: 'devise')]
    private Collection $compteAgences;

    /**
     * @var Collection<int, CompteGeneral>
     */
    #[ORM\OneToMany(targetEntity: CompteGeneral::class, mappedBy: 'devise')]
    private Collection $compteGenerals;

    /**
     * @var Collection<int, CompteBancaire>
     */
    #[ORM\OneToMany(targetEntity: CompteBancaire::class, mappedBy: 'devise')]
    private Collection $compteBancaires;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, ApproAgence>
     */
    #[ORM\OneToMany(targetEntity: ApproAgence::class, mappedBy: 'devise')]
    private Collection $approAgences;

    /**
     * @var Collection<int, ApproCaisse>
     */
    #[ORM\OneToMany(targetEntity: ApproCaisse::class, mappedBy: 'devise')]
    private Collection $approCaisses;

    /**
     * @var Collection<int, ApproCompteGeneral>
     */
    #[ORM\OneToMany(targetEntity: ApproCompteGeneral::class, mappedBy: 'devise')]
    private Collection $approCompteGenerals;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'devise')]
    private Collection $mouvementCaisses;

    /**
     * @var Collection<int, ApproCompteBancaire>
     */
    #[ORM\OneToMany(targetEntity: ApproCompteBancaire::class, mappedBy: 'devise')]
    private Collection $approCompteBancaires;

    /**
     * @var Collection<int, MouvementCompteBancaire>
     */
    #[ORM\OneToMany(targetEntity: MouvementCompteBancaire::class, mappedBy: 'devise')]
    private Collection $mouvementCompteBancaires;

    /**
     * @var Collection<int, TauxChange>
     */
    #[ORM\OneToMany(targetEntity: TauxChange::class, mappedBy: 'deviseSource')]
    private Collection $tauxChanges;

    /**
     * @var Collection<int, CompteClient>
     */
    #[ORM\OneToMany(targetEntity: CompteClient::class, mappedBy: 'devise')]
    private Collection $compteClients;

    public function __construct()
    {
        $this->compteCaisses = new ArrayCollection();
        $this->compteAgences = new ArrayCollection();
        $this->compteGenerals = new ArrayCollection();
        $this->compteBancaires = new ArrayCollection();
        $this->approAgences = new ArrayCollection();
        $this->approCaisses = new ArrayCollection();
        $this->approCompteGenerals = new ArrayCollection();
        $this->mouvementCaisses = new ArrayCollection();
        $this->approCompteBancaires = new ArrayCollection();
        $this->mouvementCompteBancaires = new ArrayCollection();
        $this->tauxChanges = new ArrayCollection();
        $this->compteClients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCodeIso(): ?string
    {
        return $this->codeIso;
    }

    public function setCodeIso(?string $codeIso): static
    {
        $this->codeIso = $codeIso;

        return $this;
    }

    public function getSymbole(): ?string
    {
        return $this->symbole;
    }

    public function setSymbole(?string $symbole): static
    {
        $this->symbole = $symbole;

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
            $compteCaiss->setDevise($this);
        }

        return $this;
    }

    public function removeCompteCaiss(CompteCaisse $compteCaiss): static
    {
        if ($this->compteCaisses->removeElement($compteCaiss)) {
            // set the owning side to null (unless already changed)
            if ($compteCaiss->getDevise() === $this) {
                $compteCaiss->setDevise(null);
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
            $compteAgence->setDevise($this);
        }

        return $this;
    }

    public function removeCompteAgence(CompteAgence $compteAgence): static
    {
        if ($this->compteAgences->removeElement($compteAgence)) {
            // set the owning side to null (unless already changed)
            if ($compteAgence->getDevise() === $this) {
                $compteAgence->setDevise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompteGeneral>
     */
    public function getCompteGenerals(): Collection
    {
        return $this->compteGenerals;
    }

    public function addCompteGeneral(CompteGeneral $compteGeneral): static
    {
        if (!$this->compteGenerals->contains($compteGeneral)) {
            $this->compteGenerals->add($compteGeneral);
            $compteGeneral->setDevise($this);
        }

        return $this;
    }

    public function removeCompteGeneral(CompteGeneral $compteGeneral): static
    {
        if ($this->compteGenerals->removeElement($compteGeneral)) {
            // set the owning side to null (unless already changed)
            if ($compteGeneral->getDevise() === $this) {
                $compteGeneral->setDevise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompteBancaire>
     */
    public function getCompteBancaires(): Collection
    {
        return $this->compteBancaires;
    }

    public function addCompteBancaire(CompteBancaire $compteBancaire): static
    {
        if (!$this->compteBancaires->contains($compteBancaire)) {
            $this->compteBancaires->add($compteBancaire);
            $compteBancaire->setDevise($this);
        }

        return $this;
    }

    public function removeCompteBancaire(CompteBancaire $compteBancaire): static
    {
        if ($this->compteBancaires->removeElement($compteBancaire)) {
            // set the owning side to null (unless already changed)
            if ($compteBancaire->getDevise() === $this) {
                $compteBancaire->setDevise(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

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
            $approAgence->setDevise($this);
        }

        return $this;
    }

    public function removeApproAgence(ApproAgence $approAgence): static
    {
        if ($this->approAgences->removeElement($approAgence)) {
            // set the owning side to null (unless already changed)
            if ($approAgence->getDevise() === $this) {
                $approAgence->setDevise(null);
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
            $approCaiss->setDevise($this);
        }

        return $this;
    }

    public function removeApproCaiss(ApproCaisse $approCaiss): static
    {
        if ($this->approCaisses->removeElement($approCaiss)) {
            // set the owning side to null (unless already changed)
            if ($approCaiss->getDevise() === $this) {
                $approCaiss->setDevise(null);
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
            $approCompteGeneral->setDevise($this);
        }

        return $this;
    }

    public function removeApproCompteGeneral(ApproCompteGeneral $approCompteGeneral): static
    {
        if ($this->approCompteGenerals->removeElement($approCompteGeneral)) {
            // set the owning side to null (unless already changed)
            if ($approCompteGeneral->getDevise() === $this) {
                $approCompteGeneral->setDevise(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MouvementCaisse>
     */
    public function getMouvementCaisses(): Collection
    {
        return $this->mouvementCaisses;
    }

    public function addMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if (!$this->mouvementCaisses->contains($mouvementCaiss)) {
            $this->mouvementCaisses->add($mouvementCaiss);
            $mouvementCaiss->setDevise($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getDevise() === $this) {
                $mouvementCaiss->setDevise(null);
            }
        }

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
            $approCompteBancaire->setDevise($this);
        }

        return $this;
    }

    public function removeApproCompteBancaire(ApproCompteBancaire $approCompteBancaire): static
    {
        if ($this->approCompteBancaires->removeElement($approCompteBancaire)) {
            // set the owning side to null (unless already changed)
            if ($approCompteBancaire->getDevise() === $this) {
                $approCompteBancaire->setDevise(null);
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
            $mouvementCompteBancaire->setDevise($this);
        }

        return $this;
    }

    public function removeMouvementCompteBancaire(MouvementCompteBancaire $mouvementCompteBancaire): static
    {
        if ($this->mouvementCompteBancaires->removeElement($mouvementCompteBancaire)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCompteBancaire->getDevise() === $this) {
                $mouvementCompteBancaire->setDevise(null);
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
            $tauxChange->setDeviseSource($this);
        }

        return $this;
    }

    public function removeTauxChange(TauxChange $tauxChange): static
    {
        if ($this->tauxChanges->removeElement($tauxChange)) {
            // set the owning side to null (unless already changed)
            if ($tauxChange->getDeviseSource() === $this) {
                $tauxChange->setDeviseSource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CompteClient>
     */
    public function getCompteClients(): Collection
    {
        return $this->compteClients;
    }

    public function addCompteClient(CompteClient $compteClient): static
    {
        if (!$this->compteClients->contains($compteClient)) {
            $this->compteClients->add($compteClient);
            $compteClient->setDevise($this);
        }

        return $this;
    }

    public function removeCompteClient(CompteClient $compteClient): static
    {
        if ($this->compteClients->removeElement($compteClient)) {
            // set the owning side to null (unless already changed)
            if ($compteClient->getDevise() === $this) {
                $compteClient->setDevise(null);
            }
        }

        return $this;
    }
}
