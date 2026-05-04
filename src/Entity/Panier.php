<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

use App\Repository\PanierRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['panier:read']],
    denormalizationContext: ['groups' => ['panier:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(),
        new Patch(),
        new Delete(),
    ]
)]
#[ApiFilter(SearchFilter::class, properties: ['id' => 'exact', 'user' => 'exact', 'statut' => 'exact'])]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['panier:read', 'itempanier:read', 'itempanier:write'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'paniers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['panier:read', 'panier:write'])]
    private ?User $user = null;

    #[ORM\Column]
    #[Groups(['panier:read', 'panier:write'])]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 7, scale: 2)]
    #[Groups(['panier:read', 'panier:write'])]
    private ?string $montantTotal = null;

    #[ORM\Column(length: 1)]
    #[Groups(['panier:read', 'panier:write'])]
    private ?string $statut = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['panier:read', 'panier:write'])]
    private ?\DateTime $dateCommande = null;

    /**
     * @var Collection<int, ItemPanier>
     */
    #[ORM\OneToMany(targetEntity: ItemPanier::class, mappedBy: 'panier', orphanRemoval: true)]
    private Collection $itemPaniers;

    public function __construct()
    {
        $this->itemPaniers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTime $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getMontantTotal(): ?string
    {
        return $this->montantTotal;
    }

    public function setMontantTotal(string $montantTotal): static
    {
        $this->montantTotal = $montantTotal;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateCommande(): ?\DateTime
    {
        return $this->dateCommande;
    }

    public function setDateCommande(?\DateTime $dateCommande): static
    {
        $this->dateCommande = $dateCommande;

        return $this;
    }

    /**
     * @return Collection<int, ItemPanier>
     */
    public function getItemPaniers(): Collection
    {
        return $this->itemPaniers;
    }

    public function addItemPanier(ItemPanier $itemPanier): static
    {
        if (!$this->itemPaniers->contains($itemPanier)) {
            $this->itemPaniers->add($itemPanier);
            $itemPanier->setPanier($this);
        }

        return $this;
    }

    public function removeItemPanier(ItemPanier $itemPanier): static
    {
        if ($this->itemPaniers->removeElement($itemPanier)) {
            // set the owning side to null (unless already changed)
            if ($itemPanier->getPanier() === $this) {
                $itemPanier->setPanier(null);
            }
        }

        return $this;
    }
}
