<?php

namespace App\Entity;

use App\Repository\AffectationCaisseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AffectationCaisseRepository::class)]
class AffectationCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'affectationCaisses')]
    private ?User $caissier = null;

    #[ORM\ManyToOne(inversedBy: 'affectationCaisses')]
    private ?Caisse $caisse = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTime $dateDebut = null; // MODIFIÉ ICI

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $dateFin = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCaissier(): ?User
    {
        return $this->caissier;
    }

    public function setCaissier(?User $caissier): static
    {
        $this->caissier = $caissier;

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

    // MÉTHODES MODIFIÉES
    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }
    // FIN DES MODIFICATIONS

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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
