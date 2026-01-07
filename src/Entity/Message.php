<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contenu = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateEnvoi = null;

    #[ORM\ManyToOne(inversedBy: 'messagesEnvoyes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $expediteur = null;

    #[ORM\ManyToOne(inversedBy: 'messagesRecus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $destinataire = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $lu = false;


    public function __construct()
    {
        $this->dateEnvoi = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateEnvoi(): ?\DateTimeImmutable
    {
        return $this->dateEnvoi;
    }

    public function setDateEnvoi(\DateTimeImmutable $dateEnvoi): static
    {
        $this->dateEnvoi = $dateEnvoi;
        return $this;
    }

    public function getExpediteur(): ?Utilisateur
    {
        return $this->expediteur;
    }

    public function setExpediteur(?Utilisateur $expediteur): static
    {
        $this->expediteur = $expediteur;
        return $this;
    }

    public function getDestinataire(): ?Utilisateur
    {
        return $this->destinataire;
    }

    public function setDestinataire(?Utilisateur $destinataire): static
    {
        $this->destinataire = $destinataire;
        return $this;
    }

    public function isLu(): bool
    {
        return $this->lu;
    }

    public function setLu(bool $lu): self
    {
        $this->lu = $lu;
        return $this;
    }

}
