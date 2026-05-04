<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    order: ['libelle' => 'ASC'],    
    normalizationContext: ['groups' => ['produit:read']],
    operations: [
        new Get(),
        new GetCollection(),
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['libelle' => 'ASC'])]
#[ApiFilter(SearchFilter::class, properties: ['categorie' => 'exact'])]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
      #[Groups(['produit:read', 'itempanier:read', 'itempanier:write'])]
    private ?int $id = null;

    #[ORM\Column(length: 40)]
    #[Assert\NotBlank(message: 'Le libellé est obligatoire')]
    #[Assert\Length(
        min: 3,
        max: 40,
        minMessage: 'Le libellé doit comporter au moins {{ limit }} caractères',
        maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères',
    )]
    #[Groups(['produit:read', 'itempanier:read'])]
    private ?string $libelle = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 7, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire')]
    #[Assert\Range(min: 0.1, max: 999)]
    #[Groups(['produit:read', 'itempanier:read'])]
    private ?string $prix = null;

    #[ORM\Column]
    #[Assert\Type("\DateTime")]
    #[Groups(['produit:read'])]
    private ?\DateTime $dateCreation = null;

    #[ORM\ManyToOne(inversedBy: 'produits')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['produit:read'])]
    private ?Categorie $categorie = null;

    // /**
    //  * @var Collection<int, Recette>
    //  */
    // #[ORM\ManyToMany(targetEntity: Recette::class, mappedBy: 'produits')]
    // private Collection $recettes;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank(message: 'La description est obligatoire')]
    #[Assert\Length(
        min: 15,
        max: 255,
        minMessage: 'La description doit comporter au moins {{ limit }} caractères',
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères',
    )]
    #[Groups(['produit:read'])]
    private ?string $description = null;

    #[ORM\Column]
    #[Groups(['produit:read'])]
    private ?bool $cru = null;

    #[ORM\Column]
    #[Groups(['produit:read'])]
    private ?bool $cuit = null;

    #[ORM\Column]
    #[Groups(['produit:read'])]
    private ?bool $bio = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type("\DateTime")]
    #[Groups(['produit:read'])]
    private ?\DateTime $debutDisponibilite = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\Type("\DateTime")]
    #[Assert\Range(minPropertyPath: "debutDisponibilite")]
    #[Groups(['produit:read'])]
    private ?\DateTime $finDisponibilite = null;

    #[ApiProperty(types: ['https://schema.org/imageUrl'])]
    #[Groups(['produit:read', 'itempanier:read'])]
    public function getImageUrl(): ?string
    {
        return '/images/produits/' . $this->imageNom;  // pour simplifier sinon var ENV
    }

    #[Vich\UploadableField(mapping: 'produits', fileNameProperty: 'imageNom', size: 'imageTaille')]
    private ?File $imageFichier = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageNom = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageTaille = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $imageDateMaj = null;

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

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

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

    public function __construct()
    {
        $this->dateCreation = new \DateTime('now');
        // $this->recettes = new ArrayCollection();
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;

        return $this;
    }

    // /**
    //  * @return Collection<int, Recette>
    //  */
    // public function getRecettes(): Collection
    // {
    //     return $this->recettes;
    // }

    // public function addRecette(Recette $recette): static
    // {
    //     if (!$this->recettes->contains($recette)) {
    //         $this->recettes->add($recette);
    //         $recette->addProduit($this);
    //     }

    //     return $this;
    // }

    // public function removeRecette(Recette $recette): static
    // {
    //     if ($this->recettes->removeElement($recette)) {
    //         $recette->removeProduit($this);
    //     }

    //     return $this;
    // }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isCru(): ?bool
    {
        return $this->cru;
    }

    public function setCru(bool $cru): static
    {
        $this->cru = $cru;

        return $this;
    }

    public function isCuit(): ?bool
    {
        return $this->cuit;
    }

    public function setCuit(bool $cuit): static
    {
        $this->cuit = $cuit;

        return $this;
    }

    public function isBio(): ?bool
    {
        return $this->bio;
    }

    public function setBio(bool $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getDebutDisponibilite(): ?\DateTime
    {
        return $this->debutDisponibilite;
    }

    public function setDebutDisponibilite(?\DateTime $debutDisponibilite): static
    {
        $this->debutDisponibilite = $debutDisponibilite;

        return $this;
    }

    public function getFinDisponibilite(): ?\DateTime
    {
        return $this->finDisponibilite;
    }

    public function setFinDisponibilite(?\DateTime $finDisponibilite): static
    {
        $this->finDisponibilite = $finDisponibilite;

        return $this;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFichier(?File $imageFichier = null): void
    {
        $this->imageFichier = $imageFichier;

        if (null !== $imageFichier) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->imageDateMaj = new \DateTimeImmutable();
        }
    }

    public function getImageFichier(): ?File
    {
        return $this->imageFichier;
    }

    public function setImageNom(?string $imageNom): void
    {
        $this->imageNom = $imageNom;
    }

    public function getImageNom(): ?string
    {
        return $this->imageNom;
    }

    public function setImageTaille(?int $imageTaille): void
    {
        $this->imageTaille = $imageTaille;
    }

    public function getImageTaille(): ?int
    {
        return $this->imageTaille;
    }
}
