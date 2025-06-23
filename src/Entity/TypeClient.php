<?php

namespace App\Entity;

use App\Repository\TypeClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TypeClientRepository::class)]
class TypeClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, ProfilClient>
     */
    #[ORM\OneToMany(targetEntity: ProfilClient::class, mappedBy: 'typeClient')]
    private Collection $profilClients;

    public function __construct()
    {
        $this->profilClients = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

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
            $profilClient->setTypeClient($this);
        }

        return $this;
    }

    public function removeProfilClient(ProfilClient $profilClient): static
    {
        if ($this->profilClients->removeElement($profilClient)) {
            // set the owning side to null (unless already changed)
            if ($profilClient->getTypeClient() === $this) {
                $profilClient->setTypeClient(null);
            }
        }

        return $this;
    }


}
