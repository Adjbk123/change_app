<?php

namespace App\Entity;

use App\Repository\ApproAgenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApproAgenceRepository::class)]
class ApproAgence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'approAgences')]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(inversedBy: 'approAgences')]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 255)]
    private ?string $statut = null;

    #[ORM\ManyToOne(inversedBy: 'approAgences')]
    private ?CompteGeneral $compteGeneral = null;

    #[ORM\ManyToOne(inversedBy: 'approAgences')]
    private ?CompteAgence $compteAgence = null;

    #[ORM\Column]
    private ?\DateTime $dateDemande = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateTraitement = null;

    #[ORM\ManyToOne(inversedBy: 'approAgences')]
    private ?User $demandeur = null;

    #[ORM\ManyToOne(inversedBy: 'approAgences')]
    private ?User $validePar = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDevise(): ?Devise
    {
        return $this->devise;
    }

    public function setDevise(?Devise $devise): static
    {
        $this->devise = $devise;

        return $this;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

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

    public function getCompteGeneral(): ?CompteGeneral
    {
        return $this->compteGeneral;
    }

    public function setCompteGeneral(?CompteGeneral $compteGeneral): static
    {
        $this->compteGeneral = $compteGeneral;

        return $this;
    }

    public function getCompteAgence(): ?CompteAgence
    {
        return $this->compteAgence;
    }

    public function setCompteAgence(?CompteAgence $compteAgence): static
    {
        $this->compteAgence = $compteAgence;

        return $this;
    }

    public function getDateDemande(): ?\DateTime
    {
        return $this->dateDemande;
    }

    public function setDateDemande(\DateTime $dateDemande): static
    {
        $this->dateDemande = $dateDemande;

        return $this;
    }

    public function getDateTraitement(): ?\DateTime
    {
        return $this->dateTraitement;
    }

    public function setDateTraitement(?\DateTime $dateTraitement): static
    {
        $this->dateTraitement = $dateTraitement;

        return $this;
    }

    public function getDemandeur(): ?User
    {
        return $this->demandeur;
    }

    public function setDemandeur(?User $demandeur): static
    {
        $this->demandeur = $demandeur;

        return $this;
    }

    public function getValidePar(): ?User
    {
        return $this->validePar;
    }

    public function setValidePar(?User $validePar): static
    {
        $this->validePar = $validePar;

        return $this;
    }
}
