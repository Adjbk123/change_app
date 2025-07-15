<?php

namespace App\Entity;

use App\Repository\ProfilClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfilClientRepository::class)]
class ProfilClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'profilClients')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'profilClients')]
    private ?TypeClient $typeClient = null;

    #[ORM\Column(length: 255)]
    private ?string $numeroProfilCompte = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isActif = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @var Collection<int, CompteClient>
     */
    #[ORM\OneToMany(targetEntity: CompteClient::class, mappedBy: 'profilClient')]
    private Collection $compteClients;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, Beneficiaire>
     */
    #[ORM\OneToMany(targetEntity: Beneficiaire::class, mappedBy: 'profilClient')]
    private Collection $beneficiaires;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'profilClient')]
    private Collection $operations;

    /**
     * @var Collection<int, Pret>
     */
    #[ORM\OneToMany(targetEntity: Pret::class, mappedBy: 'profilClient')]
    private Collection $prets;

    public function __construct()
    {
        $this->compteClients = new ArrayCollection();
        $this->beneficiaires = new ArrayCollection();
        $this->operations = new ArrayCollection();
        $this->prets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getTypeClient(): ?TypeClient
    {
        return $this->typeClient;
    }

    public function setTypeClient(?TypeClient $typeClient): static
    {
        $this->typeClient = $typeClient;

        return $this;
    }

    public function getNumeroProfilCompte(): ?string
    {
        return $this->numeroProfilCompte;
    }

    public function setNumeroProfilCompte(string $numeroProfilCompte): static
    {
        $this->numeroProfilCompte = $numeroProfilCompte;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->isActif;
    }

    public function setIsActif(?bool $isActif): static
    {
        $this->isActif = $isActif;

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
            $compteClient->setProfilClient($this);
        }

        return $this;
    }

    public function removeCompteClient(CompteClient $compteClient): static
    {
        if ($this->compteClients->removeElement($compteClient)) {
            // set the owning side to null (unless already changed)
            if ($compteClient->getProfilClient() === $this) {
                $compteClient->setProfilClient(null);
            }
        }

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Beneficiaire>
     */
    public function getBeneficiaires(): Collection
    {
        return $this->beneficiaires;
    }

    public function addBeneficiaire(Beneficiaire $beneficiaire): static
    {
        if (!$this->beneficiaires->contains($beneficiaire)) {
            $this->beneficiaires->add($beneficiaire);
            $beneficiaire->setProfilClient($this);
        }

        return $this;
    }

    public function removeBeneficiaire(Beneficiaire $beneficiaire): static
    {
        if ($this->beneficiaires->removeElement($beneficiaire)) {
            // set the owning side to null (unless already changed)
            if ($beneficiaire->getProfilClient() === $this) {
                $beneficiaire->setProfilClient(null);
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
            $operation->setProfilClient($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getProfilClient() === $this) {
                $operation->setProfilClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Pret>
     */
    public function getPrets(): Collection
    {
        return $this->prets;
    }

    public function addPret(Pret $pret): static
    {
        if (!$this->prets->contains($pret)) {
            $this->prets->add($pret);
            $pret->setProfilClient($this);
        }

        return $this;
    }

    public function removePret(Pret $pret): static
    {
        if ($this->prets->removeElement($pret)) {
            // set the owning side to null (unless already changed)
            if ($pret->getProfilClient() === $this) {
                $pret->setProfilClient(null);
            }
        }

        return $this;
    }
    public function __toString(): string
    {
        return  $this->client->getNom().' '. $this->client->getPrenoms().' | '.$this->getTypeClient()->getLibelle()." | ".$this->numeroProfilCompte;
    }
}
