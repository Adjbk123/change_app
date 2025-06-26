<?php

namespace App\Entity;

use App\Repository\BeneficiaireRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BeneficiaireRepository::class)]
class Beneficiaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nomComplet = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contact = null;

    #[ORM\Column(length: 255)]
    private ?string $typeCompte = null;

    #[ORM\Column(length: 255)]
    private ?string $detailCompte = null;

    #[ORM\Column(length: 255)]
    private ?string $institutionFinanciere = null;

    #[ORM\ManyToOne(inversedBy: 'beneficiaires')]
    private ?Pays $pays = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'beneficiaires')]
    private ?ProfilClient $profilClient = null;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'beneficiaire')]
    private Collection $operations;

    public function __construct()
    {
        $this->operations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomComplet(): ?string
    {
        return $this->nomComplet;
    }

    public function setNomComplet(string $nomComplet): static
    {
        $this->nomComplet = $nomComplet;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(?string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getTypeCompte(): ?string
    {
        return $this->typeCompte;
    }

    public function setTypeCompte(string $typeCompte): static
    {
        $this->typeCompte = $typeCompte;

        return $this;
    }

    public function getDetailCompte(): ?string
    {
        return $this->detailCompte;
    }

    public function setDetailCompte(string $detailCompte): static
    {
        $this->detailCompte = $detailCompte;

        return $this;
    }

    public function getInstitutionFinanciere(): ?string
    {
        return $this->institutionFinanciere;
    }

    public function setInstitutionFinanciere(string $institutionFinanciere): static
    {
        $this->institutionFinanciere = $institutionFinanciere;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getProfilClient(): ?ProfilClient
    {
        return $this->profilClient;
    }

    public function setProfilClient(?ProfilClient $profilClient): static
    {
        $this->profilClient = $profilClient;

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
            $operation->setBeneficiaire($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getBeneficiaire() === $this) {
                $operation->setBeneficiaire(null);
            }
        }

        return $this;
    }
}
