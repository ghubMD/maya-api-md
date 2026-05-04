<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;

// use Symfony\Component\Serializer\Annotation\Groups;

use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\OrderFilter;

use Symfony\Component\Serializer\Attribute\Groups;
use App\Repository\CategorieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CategorieRepository::class)]
#[Vich\Uploadable]
#[ApiResource(
    order: ['libelle' => 'ASC'],
    normalizationContext: ['groups' => ['categorie:read']],
    denormalizationContext: ['groups' => ['categorie:write']],
    operations: [
        new Get(),
        new GetCollection(),
        new Post(
            outputFormats: [
                'jsonld' => ['application/ld+json'],
                'json' => ['application/json']
            ],
            inputFormats: ['multipart' => ['multipart/form-data']]
        ),
        new Delete()
    ]
)]
#[ApiFilter(OrderFilter::class, properties: ['libelle' => 'ASC'])]
#[UniqueEntity(fields: ['libelle'], message: 'Ce libellé existe déjà')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['categorie:read', 'categorie:write', 'produit:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(
        message: 'Le libellé est obligatoire',
        normalizer: 'trim'
    )]
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le libellé doit comporter au moins {{ limit }} caractères',
        maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères',
    )]
    #[Groups(['categorie:read', 'categorie:write', 'produit:read'])]
    private ?string $libelle = null;

    /**
     * @var Collection<int, Produit>
     */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'categorie')]
    private Collection $produits;

    #[ApiProperty(types: ['https://schema.org/imageUrl'])]
    #[Groups(['categorie:read', 'produit:read'])]
    public function getImageUrl(): ?string
    {
        return '/images/categories/' . $this->imageNom;  // pour simplifier sinon var ENV
    }

    // NOTE: This is not a mapped field of entity metadata, just a simple property.
    #[Vich\UploadableField(mapping: 'categories', fileNameProperty: 'imageNom', size: 'imageTaille')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png'],
        mimeTypesMessage: 'Format accepté : JPEG ou PNG'
    )]
    #[Groups(['categorie:write'])]
    private ?File $imageFichier = null;

    #[ORM\Column(nullable: true)]
    private ?string $imageNom = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageTaille = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $imageDateMaj = null;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
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
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): static
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setCategorie($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): static
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getCategorie() === $this) {
                $produit->setCategorie(null);
            }
        }

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
