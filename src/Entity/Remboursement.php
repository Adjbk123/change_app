<?php

namespace App\Entity;

use App\Repository\RemboursementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RemboursementRepository::class)]
class Remboursement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'remboursements')]
    private ?Pret $pret = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montantRembourse = null;

    #[ORM\Column]
    private ?\DateTime $dateRemboursement = null;

    #[ORM\ManyToOne(inversedBy: 'remboursements')]
    private ?User $agent = null;

    #[ORM\Column(length: 255)]
    private ?string $typePaiement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPret(): ?Pret
    {
        return $this->pret;
    }

    public function setPret(?Pret $pret): static
    {
        $this->pret = $pret;

        return $this;
    }

    public function getMontantRembourse(): ?string
    {
        return $this->montantRembourse;
    }

    public function setMontantRembourse(string $montantRembourse): static
    {
        $this->montantRembourse = $montantRembourse;

        return $this;
    }

    public function getDateRemboursement(): ?\DateTime
    {
        return $this->dateRemboursement;
    }

    public function setDateRemboursement(\DateTime $dateRemboursement): static
    {
        $this->dateRemboursement = $dateRemboursement;

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

    public function getTypePaiement(): ?string
    {
        return $this->typePaiement;
    }

    public function setTypePaiement(string $typePaiement): static
    {
        $this->typePaiement = $typePaiement;

        return $this;
    }
}
