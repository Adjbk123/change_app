<?php

namespace App\Entity;

use App\Repository\CompteClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompteClientRepository::class)]
class CompteClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;


    #[ORM\ManyToOne(inversedBy: 'compteClients')]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $soldeInitial = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $soldeActuel = null;

    #[ORM\ManyToOne(inversedBy: 'compteClients')]
    private ?ProfilClient $profilClient = null;

    /**
     * @var Collection<int, MouvementCompteClient>
     */
    #[ORM\OneToMany(targetEntity: MouvementCompteClient::class, mappedBy: 'compteClient')]
    private Collection $mouvementCompteClients;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'compteClientSource')]
    private Collection $operations;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'compteClientCible')]
    private Collection $operationsc;

    public function __construct()
    {
        $this->mouvementCompteClients = new ArrayCollection();
        $this->operations = new ArrayCollection();
        $this->operationsc = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
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

    public function getProfilClient(): ?ProfilClient
    {
        return $this->profilClient;
    }

    public function setProfilClient(?ProfilClient $profilClient): static
    {
        $this->profilClient = $profilClient;

        return $this;
    }

    public function getSoldeActuel(): ?string
    {
        return $this->soldeActuel;
    }

    public function setSoldeActuel(string $soldeActuel): static
    {
        $this->soldeActuel = $soldeActuel;

        return $this;
    }

    /**
     * @return Collection<int, MouvementCompteClient>
     */
    public function getMouvementCompteClients(): Collection
    {
        return $this->mouvementCompteClients;
    }

    public function addMouvementCompteClient(MouvementCompteClient $mouvementCompteClient): static
    {
        if (!$this->mouvementCompteClients->contains($mouvementCompteClient)) {
            $this->mouvementCompteClients->add($mouvementCompteClient);
            $mouvementCompteClient->setCompteClient($this);
        }

        return $this;
    }

    public function removeMouvementCompteClient(MouvementCompteClient $mouvementCompteClient): static
    {
        if ($this->mouvementCompteClients->removeElement($mouvementCompteClient)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCompteClient->getCompteClient() === $this) {
                $mouvementCompteClient->setCompteClient(null);
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
            $operation->setCompteClientSource($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getCompteClientSource() === $this) {
                $operation->setCompteClientSource(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperationsc(): Collection
    {
        return $this->operationsc;
    }

    public function addOperationsc(Operation $operationsc): static
    {
        if (!$this->operationsc->contains($operationsc)) {
            $this->operationsc->add($operationsc);
            $operationsc->setCompteClientCible($this);
        }

        return $this;
    }

    public function removeOperationsc(Operation $operationsc): static
    {
        if ($this->operationsc->removeElement($operationsc)) {
            // set the owning side to null (unless already changed)
            if ($operationsc->getCompteClientCible() === $this) {
                $operationsc->setCompteClientCible(null);
            }
        }

        return $this;
    }

    public function deposer(float $montant): void
    {
        $this->soldeActuel += $montant;
        $this->soldeInitial += $montant;
    }

    public function retirer(float $montant): void
    {
        $this->soldeActuel -= $montant;
    }
}
