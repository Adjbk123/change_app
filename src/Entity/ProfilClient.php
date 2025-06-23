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

    public function __construct()
    {
        $this->compteClients = new ArrayCollection();
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
}
