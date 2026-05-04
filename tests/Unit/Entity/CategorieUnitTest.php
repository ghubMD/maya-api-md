<?php

// On déclare le namespace correspondant au dossier
namespace App\Tests\Unit;

// On importe la classe de base PHPUnit pour les tests unitaires simples
use PHPUnit\Framework\TestCase;

// On importe l'entité que l'on souhaite tester
use App\Entity\Categorie;

// On importe Produit car la relation OneToMany doit être testée
use App\Entity\Produit;

// On importe File pour simuler l'upload d'image
use Symfony\Component\HttpFoundation\File\File;

// On crée une classe de test qui hérite de TestCase (test unitaire pur)
class CategorieUnitTest extends TestCase
{
    // =============================
    // Test des getters / setters
    // =============================
    public function testGettersAndSetters(): void
    {
        // On instancie l'entité
        $categorie = new Categorie();

        // On appelle le setter
        $categorie->setLibelle('Informatique');

        // On vérifie que le getter retourne la bonne valeur
        $this->assertEquals('Informatique', $categorie->getLibelle());
    }

    // =============================
    // Test de la méthode calculée getImageUrl()
    // =============================
    public function testImageUrl(): void
    {
        $categorie = new Categorie();

        // On simule le nom du fichier image
        $categorie->setImageNom('test.jpg');

        // On vérifie que l'URL générée est correcte
        $this->assertEquals(
            '/images/categories/test.jpg',
            $categorie->getImageUrl()
        );
    }

    // =============================
    // Test de la relation OneToMany
    // =============================
    public function testAddAndRemoveProduit(): void
    {
        $categorie = new Categorie();

        // On crée un mock car on ne veut pas dépendre réellement de Produit
        $produit = $this->createMock(Produit::class);

        // On simule le comportement attendu
        $produit->method('setCategorie')->willReturnSelf();
        $produit->method('getCategorie')->willReturn($categorie);

        // On ajoute le produit
        $categorie->addProduit($produit);

        // On vérifie que la collection contient bien 1 élément
        $this->assertCount(1, $categorie->getProduits());

        // On retire le produit
        $categorie->removeProduit($produit);

        // On vérifie que la collection est vide
        $this->assertCount(0, $categorie->getProduits());
    }

    // =============================
    // Test des getters / setters ImageNom et ImageTaille
    // =============================
    public function testImageNomAndTailleSetterGetter(): void
    {
        // On instancie l'entité
        $categorie = new Categorie();

        // On appelle les setters
        $categorie->setImageNom('test.jpg');
        $categorie->setImageTaille(12345);

        // On vérifie que les getters retournent la bonne valeur
        $this->assertEquals('test.jpg', $categorie->getImageNom());
        $this->assertEquals(12345, $categorie->getImageTaille());
    }

    // =============================
    // Test du déclenchement de imageDateMaj
    // =============================
    public function testImageDateMajIsUpdatedWhenFileIsSet(): void
    {
        $categorie = new Categorie();

        // On crée un faux fichier
        $file = $this->createMock(File::class);

        // On appelle le setter
        $categorie->setImageFichier($file);

        // On accède à la propriété privée via réflexion
        $reflection = new \ReflectionClass($categorie);
        $property = $reflection->getProperty('imageDateMaj');
        $property->setAccessible(true);

        // On vérifie que la date a été initialisée
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $property->getValue($categorie)
        );
    }
}
