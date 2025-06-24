<?php

namespace App\Entity;

use App\Repository\ClientDocumentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientDocumentRepository::class)]
class ClientDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'clientDocuments')]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'clientDocuments')]
    private ?PieceIdentite $pieceIdentite = null;

    #[ORM\Column(length: 255)]
    private ?string $fichier = null;

    #[ORM\Column]
    private ?\DateTime $dateUpload = null;

    #[ORM\Column]
    private ?bool $isActif = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateEmission = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateExpiration = null;

    #[ORM\Column(length: 255)]
    private ?string $numero = null;

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

    public function getPieceIdentite(): ?PieceIdentite
    {
        return $this->pieceIdentite;
    }

    public function setPieceIdentite(?PieceIdentite $pieceIdentite): static
    {
        $this->pieceIdentite = $pieceIdentite;

        return $this;
    }

    public function getFichier(): ?string
    {
        return $this->fichier;
    }

    public function setFichier(string $fichier): static
    {
        $this->fichier = $fichier;

        return $this;
    }

    public function getDateUpload(): ?\DateTime
    {
        return $this->dateUpload;
    }

    public function setDateUpload(\DateTime $dateUpload): static
    {
        $this->dateUpload = $dateUpload;

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

    public function getDateEmission(): ?\DateTime
    {
        return $this->dateEmission;
    }

    public function setDateEmission(\DateTime $dateEmission): static
    {
        $this->dateEmission = $dateEmission;

        return $this;
    }

    public function getDateExpiration(): ?\DateTime
    {
        return $this->dateExpiration;
    }

    public function setDateExpiration(\DateTime $dateExpiration): static
    {
        $this->dateExpiration = $dateExpiration;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }
}
