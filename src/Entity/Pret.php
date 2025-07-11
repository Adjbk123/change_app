<?php

namespace App\Entity;

use App\Repository\PretRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PretRepository::class)]
class Pret
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'prets')]
    private ?ProfilClient $profilClient = null;

    #[ORM\ManyToOne(inversedBy: 'prets')]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantPrincipal = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantRestant = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tauxInteretAnnuel = null;

    #[ORM\Column(nullable: true)]
    private ?int $dureeMois = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montantTotalRembourse = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'prets')]
    private ?User $agentOctroi = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\ManyToOne(inversedBy: 'prets')]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(inversedBy: 'prets')]
    private ?Caisse $caisse = null;

    /**
     * @var Collection<int, Remboursement>
     */
    #[ORM\OneToMany(targetEntity: Remboursement::class, mappedBy: 'pret')]
    private Collection $remboursements;

    public function __construct()
    {
        $this->remboursements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getMontantPrincipal(): ?string
    {
        return $this->montantPrincipal;
    }

    public function setMontantPrincipal(string $montantPrincipal): static
    {
        $this->montantPrincipal = $montantPrincipal;

        return $this;
    }

    public function getMontantRestant(): ?string
    {
        return $this->montantRestant;
    }

    public function setMontantRestant(string $montantRestant): static
    {
        $this->montantRestant = $montantRestant;

        return $this;
    }

    public function getTauxInteretAnnuel(): ?string
    {
        return $this->tauxInteretAnnuel;
    }

    public function setTauxInteretAnnuel(?string $tauxInteretAnnuel): static
    {
        $this->tauxInteretAnnuel = $tauxInteretAnnuel;

        return $this;
    }

    public function getDureeMois(): ?int
    {
        return $this->dureeMois;
    }

    public function setDureeMois(?int $dureeMois): static
    {
        $this->dureeMois = $dureeMois;

        return $this;
    }

    public function getMontantTotalRembourse(): ?string
    {
        return $this->montantTotalRembourse;
    }

    public function setMontantTotalRembourse(string $montantTotalRembourse): static
    {
        $this->montantTotalRembourse = $montantTotalRembourse;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getAgentOctroi(): ?User
    {
        return $this->agentOctroi;
    }

    public function setAgentOctroi(?User $agentOctroi): static
    {
        $this->agentOctroi = $agentOctroi;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
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

    public function getCaisse(): ?Caisse
    {
        return $this->caisse;
    }

    public function setCaisse(?Caisse $caisse): static
    {
        $this->caisse = $caisse;
        return $this;
    }

    /**
     * @return Collection<int, Remboursement>
     */
    public function getRemboursements(): Collection
    {
        return $this->remboursements;
    }

    public function addRemboursement(Remboursement $remboursement): static
    {
        if (!$this->remboursements->contains($remboursement)) {
            $this->remboursements->add($remboursement);
            $remboursement->setPret($this);
        }

        return $this;
    }

    public function removeRemboursement(Remboursement $remboursement): static
    {
        if ($this->remboursements->removeElement($remboursement)) {
            // set the owning side to null (unless already changed)
            if ($remboursement->getPret() === $this) {
                $remboursement->setPret(null);
            }
        }

        return $this;
    }
}
