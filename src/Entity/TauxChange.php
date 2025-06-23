<?php

namespace App\Entity;

use App\Repository\TauxChangeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TauxChangeRepository::class)]
class TauxChange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tauxChanges')]
    private ?Agence $agence = null;

    #[ORM\ManyToOne(inversedBy: 'tauxChanges')]
    private ?Devise $deviseSource = null;

    #[ORM\ManyToOne(inversedBy: 'tauxChanges')]
    private ?Devise $deviseCible = null;

    #[ORM\Column]
    private ?float $tauxAchat = null;

    #[ORM\Column]
    private ?float $tauxVente = null;

    #[ORM\Column]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column]
    private ?bool $isActif = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateFin = null;

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

    public function getTauxAchat(): ?float
    {
        return $this->tauxAchat;
    }

    public function setTauxAchat(float $tauxAchat): static
    {
        $this->tauxAchat = $tauxAchat;

        return $this;
    }

    public function getTauxVente(): ?float
    {
        return $this->tauxVente;
    }

    public function setTauxVente(float $tauxVente): static
    {
        $this->tauxVente = $tauxVente;

        return $this;
    }

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function isActif(): ?bool
    {
        return $this->isActif;
    }

    public function setIsActif(bool $isActif): static
    {
        $this->isActif = $isActif;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(?\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }
}
