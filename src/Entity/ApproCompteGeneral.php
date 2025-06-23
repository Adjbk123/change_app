<?php

namespace App\Entity;

use App\Repository\ApproCompteGeneralRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApproCompteGeneralRepository::class)]
class ApproCompteGeneral
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'approCompteGenerals')]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 2)]
    private ?string $montant = null;

    #[ORM\ManyToOne(inversedBy: 'approCompteGenerals')]
    private ?User $approvisionnePar = null;

    #[ORM\ManyToOne(inversedBy: 'approCompteGenerals')]
    private ?CompteGeneral $compteGeneral = null;

    #[ORM\Column]
    private ?\DateTime $dateAppro = null;

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

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getApprovisionnePar(): ?User
    {
        return $this->approvisionnePar;
    }

    public function setApprovisionnePar(?User $approvisionnePar): static
    {
        $this->approvisionnePar = $approvisionnePar;

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

    public function getDateAppro(): ?\DateTime
    {
        return $this->dateAppro;
    }

    public function setDateAppro(\DateTime $dateAppro): static
    {
        $this->dateAppro = $dateAppro;

        return $this;
    }
}
