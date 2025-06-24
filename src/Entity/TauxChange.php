<?php

namespace App\Entity;

use App\Repository\TauxChangeRepository;
use Doctrine\DBAL\Types\Types; // MODIFIÉ : Import du namespace pour les types Doctrine
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

    // MODIFIÉ : Passage de float au type DECIMAL pour la précision
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 6)]
    private ?string $tauxAchat = null;

    // MODIFIÉ : Passage de float au type DECIMAL pour la précision
    #[ORM\Column(type: Types::DECIMAL, precision: 12, scale: 6)]
    private ?string $tauxVente = null;

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

    // MODIFIÉ : Le getter retourne maintenant une string
    public function getTauxAchat(): ?string
    {
        return $this->tauxAchat;
    }

    // MODIFIÉ : Le setter accepte maintenant une string
    public function setTauxAchat(string $tauxAchat): static
    {
        $this->tauxAchat = $tauxAchat;

        return $this;
    }

    // MODIFIÉ : Le getter retourne maintenant une string
    public function getTauxVente(): ?string
    {
        return $this->tauxVente;
    }

    // MODIFIÉ : Le setter accepte maintenant une string
    public function setTauxVente(string $tauxVente): static
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
