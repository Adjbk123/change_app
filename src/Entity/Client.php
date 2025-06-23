<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, ClientDocument>
     */
    #[ORM\OneToMany(targetEntity: ClientDocument::class, mappedBy: 'client')]
    private Collection $clientDocuments;


    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenoms = null;

    #[ORM\Column(length: 255)]
    private ?string $contact = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $registreCommerce = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profession = null;


    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'clients')]
    private ?User $createdBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ifu = null;

    /**
     * @var Collection<int, ProfilClient>
     */
    #[ORM\OneToMany(targetEntity: ProfilClient::class, mappedBy: 'client')]
    private Collection $profilClients;


    public function __construct()
    {
        $this->clientDocuments = new ArrayCollection();
        $this->profilClients = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, ClientDocument>
     */
    public function getClientDocuments(): Collection
    {
        return $this->clientDocuments;
    }

    public function addClientDocument(ClientDocument $clientDocument): static
    {
        if (!$this->clientDocuments->contains($clientDocument)) {
            $this->clientDocuments->add($clientDocument);
            $clientDocument->setClient($this);
        }

        return $this;
    }

    public function removeClientDocument(ClientDocument $clientDocument): static
    {
        if ($this->clientDocuments->removeElement($clientDocument)) {
            // set the owning side to null (unless already changed)
            if ($clientDocument->getClient() === $this) {
                $clientDocument->setClient(null);
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

    public function getPrenoms(): ?string
    {
        return $this->prenoms;
    }

    public function setPrenoms(string $prenoms): static
    {
        $this->prenoms = $prenoms;

        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getRegistreCommerce(): ?string
    {
        return $this->registreCommerce;
    }

    public function setRegistreCommerce(string $registreCommerce): static
    {
        $this->registreCommerce = $registreCommerce;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;

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

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): static
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getIfu(): ?string
    {
        return $this->ifu;
    }

    public function setIfu(?string $ifu): static
    {
        $this->ifu = $ifu;

        return $this;
    }

    /**
     * @return Collection<int, ProfilClient>
     */
    public function getProfilClients(): Collection
    {
        return $this->profilClients;
    }

    public function addProfilClient(ProfilClient $profilClient): static
    {
        if (!$this->profilClients->contains($profilClient)) {
            $this->profilClients->add($profilClient);
            $profilClient->setClient($this);
        }

        return $this;
    }

    public function removeProfilClient(ProfilClient $profilClient): static
    {
        if ($this->profilClients->removeElement($profilClient)) {
            // set the owning side to null (unless already changed)
            if ($profilClient->getClient() === $this) {
                $profilClient->setClient(null);
            }
        }

        return $this;
    }


}
