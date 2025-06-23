<?php

namespace App\Entity;

use App\Repository\ApproCompteBancaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApproCompteBancaireRepository::class)]
class ApproCompteBancaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'approCompteBancaires')]
    private ?CompteBancaire $compteBancaire = null;

    #[ORM\ManyToOne(inversedBy: 'approCompteBancaires')]
    private ?Devise $devise = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $montant = null;

    #[ORM\Column]
    private ?\DateTime $dateAppro = null;

    public function getId(): ?int
    {
        return $this->id;
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
