<?php

namespace App\Entity;

use App\Repository\PieceIdentiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PieceIdentiteRepository::class)]
class PieceIdentite
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $libelle = null;

    /**
     * @var Collection<int, ClientDocument>
     */
    #[ORM\OneToMany(targetEntity: ClientDocument::class, mappedBy: 'pieceIdentite')]
    private Collection $clientDocuments;

    public function __construct()
    {
        $this->clientDocuments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): static
    {
        $this->libelle = $libelle;

        return $this;
    }

    /**
     * @return Collection<int, ClientDocument>
     */
    public function getClientDocuments(): Collection
    {
        return $this->clientDocuments;
    }

    public function addClientDocument(ClientDocument $clientDocument): static
    {
        if (!$this->clientDocuments->contains($clientDocument)) {
            $this->clientDocuments->add($clientDocument);
            $clientDocument->setPieceIdentite($this);
        }

        return $this;
    }

    public function removeClientDocument(ClientDocument $clientDocument): static
    {
        if ($this->clientDocuments->removeElement($clientDocument)) {
            // set the owning side to null (unless already changed)
            if ($clientDocument->getPieceIdentite() === $this) {
                $clientDocument->setPieceIdentite(null);
            }
        }

        return $this;
    }
}
