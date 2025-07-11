<?php

namespace App\Entity;

use App\Repository\MouvementCompteBancaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementCompteBancaireRepository::class)]
class MouvementCompteBancaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCompteBancaires')]
    private ?CompteBancaire $compteBancaire = null;

    #[ORM\Column(length: 255)]
    private ?string $typeMouvement = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $montant = null;

    #[ORM\Column(length: 255)]
    private ?string $sens = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCompteBancaires')]
    private ?Devise $devise = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCompteBancaires')]
    private ?User $effectuePar = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateMouvement = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dateValeur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $referenceBancaire = null;

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

    public function getTypeMouvement(): ?string
    {
        return $this->typeMouvement;
    }

    public function setTypeMouvement(string $typeMouvement): static
    {
        $this->typeMouvement = $typeMouvement;

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

    public function getSens(): ?string
    {
        return $this->sens;
    }

    public function setSens(string $sens): static
    {
        $this->sens = $sens;

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

    public function getEffectuePar(): ?User
    {
        return $this->effectuePar;
    }

    public function setEffectuePar(?User $effectuePar): static
    {
        $this->effectuePar = $effectuePar;

        return $this;
    }

    public function getDateMouvement(): ?\DateTimeImmutable
    {
        return $this->dateMouvement;
    }

    public function setDateMouvement(\DateTimeImmutable $dateMouvement): static
    {
        $this->dateMouvement = $dateMouvement;

        return $this;
    }

    public function getDateValeur(): ?\DateTimeImmutable
    {
        return $this->dateValeur;
    }

    public function setDateValeur(\DateTimeImmutable $dateValeur): static
    {
        $this->dateValeur = $dateValeur;

        return $this;
    }

    public function getReferenceBancaire(): ?string
    {
        return $this->referenceBancaire;
    }

    public function setReferenceBancaire(?string $referenceBancaire): static
    {
        $this->referenceBancaire = $referenceBancaire;

        return $this;
    }
}
