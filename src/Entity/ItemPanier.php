<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

use App\Repository\ItemPanierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ItemPanierRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['itempanier:read']],
    denormalizationContext: ['groups' => ['itempanier:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(),
        new Delete(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['id' => 'exact', 'panier' => 'exact', 'produit' => 'exact'])]
class ItemPanier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['itempanier:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'itemPaniers')]
    #[ORM\JoinColumn(nullable: false, onDelete: "cascade")]
    #[Groups(['itempanier:read', 'itempanier:write'])]
    private ?Panier $panier = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['itempanier:read', 'itempanier:write'])]
    private ?Produit $produit = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 7, scale: 2, nullable: true)]
    #[Groups(['itempanier:read', 'itempanier:write'])]
    private ?string $prixInitial = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 7, scale: 2)]
    #[Groups(['itempanier:read', 'itempanier:write'])]
    private ?string $prix = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 7, scale: 2)]
    #[Groups(['itempanier:read', 'itempanier:write'])]
    private ?string $quantite = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPanier(): ?Panier
    {
        return $this->panier;
    }

    public function setPanier(?Panier $panier): static
    {
        $this->panier = $panier;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): static
    {
        $this->produit = $produit;

        return $this;
    }

    public function getPrixInitial(): ?string
    {
        return $this->prixInitial;
    }

    public function setPrixInitial(?string $prixInitial): static
    {
        $this->prixInitial = $prixInitial;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getQuantite(): ?string
    {
        return $this->quantite;
    }

    public function setQuantite(string $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }
}
