<?php

namespace App\Entity;

use App\Repository\MouvementCaisseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MouvementCaisseRepository::class)]
class MouvementCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    private ?CompteCaisse $compteCaisse = null;

    #[ORM\Column(length: 255)]
    private ?string $typeMouvement = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 20, scale: 3)]
    private ?string $montant = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    private ?Devise $devise = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementCaisses')]
    private ?User $effectuePar = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateMouvement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompteCaisse(): ?CompteCaisse
    {
        return $this->compteCaisse;
    }

    public function setCompteCaisse(?CompteCaisse $compteCaisse): static
    {
        $this->compteCaisse = $compteCaisse;

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
}
