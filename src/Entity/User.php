<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Scheb\TwoFactorBundle\Model\Email\TwoFactorInterface as Email2FAInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un compte existe déjà avec cet email.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface, Email2FAInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenoms = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\OneToMany(targetEntity: AffectationAgence::class, mappedBy: 'user')]
    private Collection $affectationAgences;

    #[ORM\Column]
    private ?bool $isActive = null;

    #[ORM\ManyToOne(inversedBy: 'users')]
    private ?Agence $agence = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isEmailAuthEnabled = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isSmsAuthEnabled = false;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $authCode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $avatar = null;

    /**
     * @var Collection<int, Agence>
     */
    #[ORM\OneToMany(targetEntity: Agence::class, mappedBy: 'responsable')]
    private Collection $agences;

    /**
     * @var Collection<int, ApproAgence>
     */
    #[ORM\OneToMany(targetEntity: ApproAgence::class, mappedBy: 'demandeur')]
    private Collection $approAgences;

    /**
     * @var Collection<int, ApproCaisse>
     */
    #[ORM\OneToMany(targetEntity: ApproCaisse::class, mappedBy: 'demandeur')]
    private Collection $approCaisses;

    /**
     * @var Collection<int, ApproCaisse>
     */
    #[ORM\OneToMany(targetEntity: ApproCaisse::class, mappedBy: 'validePar')]
    private Collection $approCaissesV;

    /**
     * @var Collection<int, ApproCompteGeneral>
     */
    #[ORM\OneToMany(targetEntity: ApproCompteGeneral::class, mappedBy: 'approvisionnePar')]
    private Collection $approCompteGenerals;

    /**
     * @var Collection<int, MouvementCaisse>
     */
    #[ORM\OneToMany(targetEntity: MouvementCaisse::class, mappedBy: 'effectuePar')]
    private Collection $mouvementCaisses;

    /**
     * @var Collection<int, MouvementCompteBancaire>
     */
    #[ORM\OneToMany(targetEntity: MouvementCompteBancaire::class, mappedBy: 'effectuePar')]
    private Collection $mouvementCompteBancaires;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\OneToMany(targetEntity: Client::class, mappedBy: 'createdBy')]
    private Collection $clients;

    /**
     * @var Collection<int, MouvementCompteClient>
     */
    #[ORM\OneToMany(targetEntity: MouvementCompteClient::class, mappedBy: 'effectuePar')]
    private Collection $mouvementCompteClients;

    /**
     * @var Collection<int, Operation>
     */
    #[ORM\OneToMany(targetEntity: Operation::class, mappedBy: 'agent')]
    private Collection $operations;

    /**
     * @var Collection<int, AffectationCaisse>
     */
    #[ORM\OneToMany(targetEntity: AffectationCaisse::class, mappedBy: 'caissier')]
    private Collection $affectationCaisses;

    /**
     * @var Collection<int, Pret>
     */
    #[ORM\OneToMany(targetEntity: Pret::class, mappedBy: 'agentOctroi')]
    private Collection $prets;

    /**
     * @var Collection<int, Remboursement>
     */
    #[ORM\OneToMany(targetEntity: Remboursement::class, mappedBy: 'agent')]
    private Collection $remboursements;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pushToken = null;

    public function __construct()
    {
        $this->affectationAgences = new ArrayCollection();
        $this->agences = new ArrayCollection();
        $this->approAgences = new ArrayCollection();
        $this->approCaisses = new ArrayCollection();
        $this->approCaissesV = new ArrayCollection();
        $this->approCompteGenerals = new ArrayCollection();
        $this->mouvementCaisses = new ArrayCollection();
        $this->mouvementCompteBancaires = new ArrayCollection();
        $this->clients = new ArrayCollection();
        $this->mouvementCompteClients = new ArrayCollection();
        $this->operations = new ArrayCollection();
        $this->affectationCaisses = new ArrayCollection();
        $this->prets = new ArrayCollection();
        $this->remboursements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenoms(): ?string
    {
        return $this->prenoms;
    }

    public function setPrenoms(string $prenoms): static
    {
        $this->prenoms = $prenoms;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // Clean sensitive data here if needed
    }

    public function getAffectationAgences(): Collection
    {
        return $this->affectationAgences;
    }

    public function addAffectationAgence(AffectationAgence $affectationAgence): static
    {
        if (!$this->affectationAgences->contains($affectationAgence)) {
            $this->affectationAgences->add($affectationAgence);
            $affectationAgence->setUser($this);
        }
        return $this;
    }

    public function removeAffectationAgence(AffectationAgence $affectationAgence): static
    {
        if ($this->affectationAgences->removeElement($affectationAgence)) {
            if ($affectationAgence->getUser() === $this) {
                $affectationAgence->setUser(null);
            }
        }
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getNomComplet(): string
    {
        return trim($this->prenoms . ' ' . $this->nom);
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    // === 2FA - EMAIL ===
    public function isEmailAuthEnabled(): bool
    {
        return $this->email !== null
            && filter_var($this->email, FILTER_VALIDATE_EMAIL)
            && $this->isEmailAuthEnabled;
    }

    public function setIsEmailAuthEnabled(bool $enabled): self
    {
        $this->isEmailAuthEnabled = $enabled;
        return $this;
    }

    public function getEmailAuthRecipient(): string
    {
        return $this->email;
    }

    public function getEmailAuthCode(): ?string
    {
        return $this->authCode;
    }

    public function setEmailAuthCode(string $authCode): void
    {
        $this->authCode = $authCode;
    }

    // === 2FA - SMS ===
    public function isTextAuthEnabled(): bool
    {
        return $this->telephone !== null
            && preg_match('/^\+?[0-9]{6,}$/', $this->telephone)
            && $this->isSmsAuthEnabled;
    }

    public function isSmsAuthEnabled(): bool
    {
        return $this->isSmsAuthEnabled;
    }

    public function setIsSmsAuthEnabled(bool $enabled): self
    {
        $this->isSmsAuthEnabled = $enabled;
        return $this;
    }

    public function getTextAuthRecipient(): string
    {
        return $this->telephone;
    }

    public function getTextAuthCode(): string
    {
        return $this->authCode ?? '';
    }

    public function setTextAuthCode(string $code): void
    {
        $this->authCode = $code;
    }

    /**
     * @return Collection<int, Agence>
     */
    public function getAgences(): Collection
    {
        return $this->agences;
    }

    public function addAgence(Agence $agence): static
    {
        if (!$this->agences->contains($agence)) {
            $this->agences->add($agence);
            $agence->setResponsable($this);
        }

        return $this;
    }

    public function removeAgence(Agence $agence): static
    {
        if ($this->agences->removeElement($agence)) {
            // set the owning side to null (unless already changed)
            if ($agence->getResponsable() === $this) {
                $agence->setResponsable(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApproAgence>
     */
    public function getApproAgences(): Collection
    {
        return $this->approAgences;
    }

    public function addApproAgence(ApproAgence $approAgence): static
    {
        if (!$this->approAgences->contains($approAgence)) {
            $this->approAgences->add($approAgence);
            $approAgence->setDemandeur($this);
        }

        return $this;
    }

    public function removeApproAgence(ApproAgence $approAgence): static
    {
        if ($this->approAgences->removeElement($approAgence)) {
            // set the owning side to null (unless already changed)
            if ($approAgence->getDemandeur() === $this) {
                $approAgence->setDemandeur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApproCaisse>
     */
    public function getApproCaisses(): Collection
    {
        return $this->approCaisses;
    }

    public function addApproCaiss(ApproCaisse $approCaiss): static
    {
        if (!$this->approCaisses->contains($approCaiss)) {
            $this->approCaisses->add($approCaiss);
            $approCaiss->setDemandeur($this);
        }

        return $this;
    }

    public function removeApproCaiss(ApproCaisse $approCaiss): static
    {
        if ($this->approCaisses->removeElement($approCaiss)) {
            // set the owning side to null (unless already changed)
            if ($approCaiss->getDemandeur() === $this) {
                $approCaiss->setDemandeur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApproCaisse>
     */
    public function getApproCaissesV(): Collection
    {
        return $this->approCaissesV;
    }

    public function addApproCaissesV(ApproCaisse $approCaissesV): static
    {
        if (!$this->approCaissesV->contains($approCaissesV)) {
            $this->approCaissesV->add($approCaissesV);
            $approCaissesV->setValidePar($this);
        }

        return $this;
    }

    public function removeApproCaissesV(ApproCaisse $approCaissesV): static
    {
        if ($this->approCaissesV->removeElement($approCaissesV)) {
            // set the owning side to null (unless already changed)
            if ($approCaissesV->getValidePar() === $this) {
                $approCaissesV->setValidePar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ApproCompteGeneral>
     */
    public function getApproCompteGenerals(): Collection
    {
        return $this->approCompteGenerals;
    }

    public function addApproCompteGeneral(ApproCompteGeneral $approCompteGeneral): static
    {
        if (!$this->approCompteGenerals->contains($approCompteGeneral)) {
            $this->approCompteGenerals->add($approCompteGeneral);
            $approCompteGeneral->setApprovisionnePar($this);
        }

        return $this;
    }

    public function removeApproCompteGeneral(ApproCompteGeneral $approCompteGeneral): static
    {
        if ($this->approCompteGenerals->removeElement($approCompteGeneral)) {
            // set the owning side to null (unless already changed)
            if ($approCompteGeneral->getApprovisionnePar() === $this) {
                $approCompteGeneral->setApprovisionnePar(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return  $this->getNom()." ".$this->getPrenoms();
    }

    /**
     * @return Collection<int, MouvementCaisse>
     */
    public function getMouvementCaisses(): Collection
    {
        return $this->mouvementCaisses;
    }

    public function addMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if (!$this->mouvementCaisses->contains($mouvementCaiss)) {
            $this->mouvementCaisses->add($mouvementCaiss);
            $mouvementCaiss->setEffectuePar($this);
        }

        return $this;
    }

    public function removeMouvementCaiss(MouvementCaisse $mouvementCaiss): static
    {
        if ($this->mouvementCaisses->removeElement($mouvementCaiss)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCaiss->getEffectuePar() === $this) {
                $mouvementCaiss->setEffectuePar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MouvementCompteBancaire>
     */
    public function getMouvementCompteBancaires(): Collection
    {
        return $this->mouvementCompteBancaires;
    }

    public function addMouvementCompteBancaire(MouvementCompteBancaire $mouvementCompteBancaire): static
    {
        if (!$this->mouvementCompteBancaires->contains($mouvementCompteBancaire)) {
            $this->mouvementCompteBancaires->add($mouvementCompteBancaire);
            $mouvementCompteBancaire->setEffectuePar($this);
        }

        return $this;
    }

    public function removeMouvementCompteBancaire(MouvementCompteBancaire $mouvementCompteBancaire): static
    {
        if ($this->mouvementCompteBancaires->removeElement($mouvementCompteBancaire)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCompteBancaire->getEffectuePar() === $this) {
                $mouvementCompteBancaire->setEffectuePar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Client>
     */
    public function getClients(): Collection
    {
        return $this->clients;
    }

    public function addClient(Client $client): static
    {
        if (!$this->clients->contains($client)) {
            $this->clients->add($client);
            $client->setCreatedBy($this);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        if ($this->clients->removeElement($client)) {
            // set the owning side to null (unless already changed)
            if ($client->getCreatedBy() === $this) {
                $client->setCreatedBy(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MouvementCompteClient>
     */
    public function getMouvementCompteClients(): Collection
    {
        return $this->mouvementCompteClients;
    }

    public function addMouvementCompteClient(MouvementCompteClient $mouvementCompteClient): static
    {
        if (!$this->mouvementCompteClients->contains($mouvementCompteClient)) {
            $this->mouvementCompteClients->add($mouvementCompteClient);
            $mouvementCompteClient->setEffectuePar($this);
        }

        return $this;
    }

    public function removeMouvementCompteClient(MouvementCompteClient $mouvementCompteClient): static
    {
        if ($this->mouvementCompteClients->removeElement($mouvementCompteClient)) {
            // set the owning side to null (unless already changed)
            if ($mouvementCompteClient->getEffectuePar() === $this) {
                $mouvementCompteClient->setEffectuePar(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Operation>
     */
    public function getOperations(): Collection
    {
        return $this->operations;
    }

    public function addOperation(Operation $operation): static
    {
        if (!$this->operations->contains($operation)) {
            $this->operations->add($operation);
            $operation->setAgent($this);
        }

        return $this;
    }

    public function removeOperation(Operation $operation): static
    {
        if ($this->operations->removeElement($operation)) {
            // set the owning side to null (unless already changed)
            if ($operation->getAgent() === $this) {
                $operation->setAgent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AffectationCaisse>
     */
    public function getAffectationCaisses(): Collection
    {
        return $this->affectationCaisses;
    }

    public function addAffectationCaiss(AffectationCaisse $affectationCaiss): static
    {
        if (!$this->affectationCaisses->contains($affectationCaiss)) {
            $this->affectationCaisses->add($affectationCaiss);
            $affectationCaiss->setCaissier($this);
        }

        return $this;
    }

    public function removeAffectationCaiss(AffectationCaisse $affectationCaiss): static
    {
        if ($this->affectationCaisses->removeElement($affectationCaiss)) {
            // set the owning side to null (unless already changed)
            if ($affectationCaiss->getCaissier() === $this) {
                $affectationCaiss->setCaissier(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Pret>
     */
    public function getPrets(): Collection
    {
        return $this->prets;
    }

    public function addPret(Pret $pret): static
    {
        if (!$this->prets->contains($pret)) {
            $this->prets->add($pret);
            $pret->setAgentOctroi($this);
        }

        return $this;
    }

    public function removePret(Pret $pret): static
    {
        if ($this->prets->removeElement($pret)) {
            // set the owning side to null (unless already changed)
            if ($pret->getAgentOctroi() === $this) {
                $pret->setAgentOctroi(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Remboursement>
     */
    public function getRemboursements(): Collection
    {
        return $this->remboursements;
    }

    public function addRemboursement(Remboursement $remboursement): static
    {
        if (!$this->remboursements->contains($remboursement)) {
            $this->remboursements->add($remboursement);
            $remboursement->setAgent($this);
        }

        return $this;
    }

    public function removeRemboursement(Remboursement $remboursement): static
    {
        if ($this->remboursements->removeElement($remboursement)) {
            // set the owning side to null (unless already changed)
            if ($remboursement->getAgent() === $this) {
                $remboursement->setAgent(null);
            }
        }

        return $this;
    }

    public function getPushToken(): ?string
    {
        return $this->pushToken;
    }

    public function setPushToken(?string $pushToken): static
    {
        $this->pushToken = $pushToken;

        return $this;
    }
}
