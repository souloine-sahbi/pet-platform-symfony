<?php

namespace App\Entity;

use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\InheritanceType("SINGLE_TABLE")]
#[ORM\DiscriminatorColumn(name: "type", type: "string")]
#[ORM\DiscriminatorMap(["admin" => Admin::class, "client" => Client::class])]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
abstract class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoProfil = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column]
    private array $roles = [];

    // CORRECTION: Utilisez Collection, pas App\Entity\Collection
    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'demandeur')]
    private Collection $demandesEnvoyees;

    #[ORM\OneToMany(targetEntity: Demande::class, mappedBy: 'destinataire')]
    private Collection $demandesRecues;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'expediteur')]
    private Collection $messagesEnvoyes;

    #[ORM\OneToMany(targetEntity: Message::class, mappedBy: 'destinataire')]
    private Collection $messagesRecus;

    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'evaluateur')]
    private Collection $evaluationsEnvoyees;

    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'evalue')]
    private Collection $evaluationsRecues;

    #[ORM\OneToMany(targetEntity: Annonce::class, mappedBy: 'auteur')]
    private Collection $annonces;

    #[ORM\OneToMany(targetEntity: Animal::class, mappedBy: 'proprietaire')]
    private Collection $animaux;

    public function __construct()
    {
        $this->demandesEnvoyees = new ArrayCollection();
        $this->demandesRecues = new ArrayCollection();
        $this->messagesEnvoyes = new ArrayCollection();
        $this->messagesRecus = new ArrayCollection();
        $this->evaluationsEnvoyees = new ArrayCollection();
        $this->evaluationsRecues = new ArrayCollection();
        $this->annonces = new ArrayCollection();
        $this->animaux = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photoProfil;
    }

    public function setPhotoProfil(?string $photoProfil): static
    {
        $this->photoProfil = $photoProfil;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;
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

    public function getRoles(): array
    {
        $roles = $this->roles;
        // Ajouter ROLE_USER par défaut
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function eraseCredentials(): void
    {
        // Nettoyez les données temporaires si nécessaire
    }

    public function getNomComplet(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    // Getters pour les relations

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandesEnvoyees(): Collection
    {
        return $this->demandesEnvoyees;
    }

    public function addDemandesEnvoyee(Demande $demandesEnvoyee): static
    {
        if (!$this->demandesEnvoyees->contains($demandesEnvoyee)) {
            $this->demandesEnvoyees->add($demandesEnvoyee);
            $demandesEnvoyee->setDemandeur($this);
        }

        return $this;
    }

    public function removeDemandesEnvoyee(Demande $demandesEnvoyee): static
    {
        if ($this->demandesEnvoyees->removeElement($demandesEnvoyee)) {
            // set the owning side to null (unless already changed)
            if ($demandesEnvoyee->getDemandeur() === $this) {
                $demandesEnvoyee->setDemandeur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Demande>
     */
    public function getDemandesRecues(): Collection
    {
        return $this->demandesRecues;
    }

    public function addDemandesRecue(Demande $demandesRecue): static
    {
        if (!$this->demandesRecues->contains($demandesRecue)) {
            $this->demandesRecues->add($demandesRecue);
            $demandesRecue->setDestinataire($this);
        }

        return $this;
    }

    public function removeDemandesRecue(Demande $demandesRecue): static
    {
        if ($this->demandesRecues->removeElement($demandesRecue)) {
            // set the owning side to null (unless already changed)
            if ($demandesRecue->getDestinataire() === $this) {
                $demandesRecue->setDestinataire(null);
            }
        }

        return $this;
    }

    // ... Ajoutez les autres getters/setters pour les relations
    // (messagesEnvoyes, messagesRecus, evaluationsEnvoyees, evaluationsRecues, annonces, animaux)
}
