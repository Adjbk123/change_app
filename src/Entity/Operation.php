<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OperationRepository::class)]
class Operation
{

    public const TYPE_ACHAT = 'ACHAT';
    public const TYPE_VENTE = 'VENTE';
    public const TYPE_DEPOT = 'DÉPÔT';
    public const TYPE_RETRAIT = 'RETRAIT';
    public const TYPE_TRANSFERT = 'TRANSFERT';


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?CompteClient $compteClientSource = null;

    #[ORM\ManyToOne(inversedBy: 'operationsc')]
    private ?CompteClient $compteClientCible = null;

    #[ORM\Column(length: 255)]
    private ?string $typeOperation = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Devise $deviseSource = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Devise $deviseCible = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $montantSource = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $montantCible = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6)]
    private ?string $taux = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?User $agent = null;

    #[ORM\Column(length: 255)]
    private ?string $sens = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Caisse $caisse = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?Beneficiaire $beneficiaire = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?ProfilClient $profilClient = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateFinalisation = null;

    #[ORM\ManyToOne(inversedBy: 'operations')]
    private ?CompteBancaire $compteBancaire = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isFinalise = null;

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

    public function getCompteClientSource(): ?CompteClient
    {
        return $this->compteClientSource;
    }

    public function setCompteClientSource(?CompteClient $compteClientSource): static
    {
        $this->compteClientSource = $compteClientSource;

        return $this;
    }

    public function getCompteClientCible(): ?CompteClient
    {
        return $this->compteClientCible;
    }

    public function setCompteClientCible(?CompteClient $compteClientCible): static
    {
        $this->compteClientCible = $compteClientCible;

        return $this;
    }

    public function getTypeOperation(): ?string
    {
        return $this->typeOperation;
    }

    public function setTypeOperation(string $typeOperation): static
    {
        $this->typeOperation = $typeOperation;

        return $this;
    }

    public function getDeviseSource(): ?Devise
    {
        return $this->deviseSource;
    }

    public function setDeviseSource(?Devise $deviseSource): static
    {
        $this->deviseSource = $deviseSource;

        return $this;
    }

    public function getDeviseCible(): ?Devise
    {
        return $this->deviseCible;
    }

    public function setDeviseCible(?Devise $deviseCible): static
    {
        $this->deviseCible = $deviseCible;

        return $this;
    }

    public function getMontantSource(): ?string
    {
        return $this->montantSource;
    }

    public function setMontantSource(string $montantSource): static
    {
        $this->montantSource = $montantSource;

        return $this;
    }

    public function getMontantCible(): ?string
    {
        return $this->montantCible;
    }

    public function setMontantCible(string $montantCible): static
    {
        $this->montantCible = $montantCible;

        return $this;
    }

    public function getTaux(): ?string
    {
        return $this->taux;
    }

    public function setTaux(string $taux): static
    {
        $this->taux = $taux;

        return $this;
    }

    public function getAgent(): ?User
    {
        return $this->agent;
    }

    public function setAgent(?User $agent): static
    {
        $this->agent = $agent;

        return $this;
    }

    public function getSens(): ?string
    {
        return $this->sens;
    }

    public function setSens(string $sens): static
    {
        $this->sens = $sens;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getBeneficiaire(): ?Beneficiaire
    {
        return $this->beneficiaire;
    }

    public function setBeneficiaire(?Beneficiaire $beneficiaire): static
    {
        $this->beneficiaire = $beneficiaire;

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

    public function getDateFinalisation(): ?\DateTimeImmutable
    {
        return $this->dateFinalisation;
    }

    public function setDateFinalisation(\DateTimeImmutable $dateFinalisation): static
    {
        $this->dateFinalisation = $dateFinalisation;

        return $this;
    }

    public function getCompteBancaire(): ?CompteBancaire
    {
        return $this->compteBancaire;
    }

    public function setCompteBancaire(?CompteBancaire $compteBancaire): static
    {
        $this->compteBancaire = $compteBancaire;

        return $this;
    }

    public function isFinalise(): ?bool
    {
        return $this->isFinalise;
    }

    public function setIsFinalise(?bool $isFinalise): static
    {
        $this->isFinalise = $isFinalise;

        return $this;
    }
}
