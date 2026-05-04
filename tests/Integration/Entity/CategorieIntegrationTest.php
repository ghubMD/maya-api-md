<?php

namespace App\Tests\Integration\Entity;

use App\Entity\Categorie;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * TEST D'INTEGRATION  — ENTITE Categorie
 *
 * Objectif : Vérifier que l'entité Categorie fonctionne correctement:
 * avec :
 *  - Doctrine
 *  - Validator
 *  - VichUploader
 *  - Base de données de test
 */
class CategorieIntegrationTest extends KernelTestCase
{
    private ?EntityManagerInterface $em = null;
    private ?ValidatorInterface $validator = null;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        // 1️⃣ Démarrage complet du Kernel Symfony
        self::bootKernel();

        // 2️⃣ Récupération des services réels depuis le container
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->validator = static::getContainer()->get(ValidatorInterface::class);

        // 3️⃣ Service pour gérer les fichiers (création / suppression)
        $this->filesystem = new Filesystem();
    }

    /**
     * 🧪 TEST 1 — VALIDATOR (contraintes NotBlank et Length)
     */
    public function testValidatorConstraints(): void
    {
        $categorie = new Categorie();

        // Cas 1 : libelle vide → doit échouer
        $categorie->setLibelle('');
        $errors = $this->validator->validate($categorie);

        $this->assertGreaterThan(0, count($errors), 'Le libellé vide doit générer une erreur');
        $this->assertCount(2, $errors); // On s'attend à 2 violations
        $this->assertSame('Le libellé est obligatoire', $errors[0]->getMessage()); // Vérifie le message
        $this->assertSame('Le libellé doit comporter au moins 3 caractères', $errors[1]->getMessage()); // Vérifie le message

        // Cas 2 : libelle trop court
        $categorie->setLibelle('ab');
        $errors = $this->validator->validate($categorie);

        $this->assertGreaterThan(0, count($errors), 'Un libellé trop court doit générer une erreur');
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('au moins 3 caractères', $errors[0]->getMessage());

        // Cas 2 : libelle trop long
        $categorie->setLibelle(str_repeat('A', 51)); // 51 caractères > max=50
        $errors = $this->validator->validate($categorie);

        $this->assertGreaterThan(0, count($errors));
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('ne peut pas dépasser 50 caractères', $errors[0]->getMessage());

        // Cas 3 : libelle valide
        $categorie->setLibelle('Categorie Valide');
        $errors = $this->validator->validate($categorie);

        $this->assertCount(0, $errors, 'Un libellé valide ne doit générer aucune erreur');
    }

    /**
     * 🧪 TEST 2 — Création avec VichUploader
     */
    public function testCreateCategorieWithImage(): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('Test Creation');

        // Création fichier temporaire
        $tmpFile = tempnam(sys_get_temp_dir(), 'vich');
        file_put_contents($tmpFile, 'image content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        // Affectation image
        $categorie->setImageFichier($uploadedFile);

        // Vérification que imageDateMaj est bien définie
        $this->assertNotNull($categorie->getImageFichier());
        $imageDateMaj = $this->getPrivateProperty($categorie, 'imageDateMaj');
        $this->assertInstanceOf(
            \DateTimeImmutable::class,
            $imageDateMaj,
            'imageDateMaj doit être défini après upload'
        );

        // Persistance
        $this->em->persist($categorie);
        $this->em->flush();

        $this->assertNotNull($categorie->getId(), 'ID généré après flush');

        // Vérification récupération base
        $fromDb = $this->em->getRepository(Categorie::class)->find($categorie->getId());
        $this->assertInstanceOf(Categorie::class, $fromDb);

        unlink($tmpFile);
    }

    /**
     * 🧪 TEST 3 — Mise à jour de l’image
     */
    public function testUpdateImage(): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('Test Update');

        // Première image
        $file1 = tempnam(sys_get_temp_dir(), 'vich1');
        file_put_contents($file1, 'first image');

        $uploaded1 = new UploadedFile($file1, 'first.jpg', 'image/jpeg', null, true);
        $categorie->setImageFichier($uploaded1);

        $this->em->persist($categorie);
        $this->em->flush();

        $firstUpdateDate = $this->getPrivateProperty($categorie, 'imageDateMaj');

        sleep(1); // Garantit différence temporelle

        // Deuxième image
        $file2 = tempnam(sys_get_temp_dir(), 'vich2');
        file_put_contents($file2, 'second image');

        $uploaded2 = new UploadedFile($file2, 'second.jpg', 'image/jpeg', null, true);
        $categorie->setImageFichier($uploaded2);

        $this->em->flush();

        $secondUpdateDate = $this->getPrivateProperty($categorie, 'imageDateMaj');
        $this->assertNotEquals(
            $firstUpdateDate,
            $secondUpdateDate,
            'La date doit changer lors du nouvel upload'
        );

        unlink($file1);
        unlink($file2);
    }

    /**
     * 🧪 TEST 4 — Suppression image
     */
    public function testRemoveImage(): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('Test Remove');

        $file = tempnam(sys_get_temp_dir(), 'vich');
        file_put_contents($file, 'image content');

        $uploaded = new UploadedFile($file, 'delete.jpg', 'image/jpeg', null, true);
        $categorie->setImageFichier($uploaded);

        $this->em->persist($categorie);
        $this->em->flush();

        // Suppression logique de l’image
        $categorie->setImageFichier(null);
        $categorie->setImageNom(null);
        $categorie->setImageTaille(null);

        $this->em->flush();

        $this->assertNull($categorie->getImageNom());
        $this->assertNull($categorie->getImageTaille());

        unlink($file);
    }

    /**
     * Méthode utilitaire permettant d'accéder à une propriété privée via Reflection
     */
    private function getPrivateProperty(object $object, string $property)
    {
        $reflectionClass = new \ReflectionClass($object);
        $reflectionProperty = $reflectionClass->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->em) {
            $this->em->close();
            $this->em = null;
        }
    }

    /**
     * 🧪 TEST 5 — Libellé unique
     */
    public function testUniqueLibelle(): void
    {
        $cat1 = new Categorie(); // Création première entité
        $cat1->setLibelle('Sport'); // Définition du libellé
        $this->em->persist($cat1); // Mise en gestion Doctrine
        $this->em->flush(); // Insertion réelle en base

        $cat2 = new Categorie(); // Création seconde entité
        $cat2->setLibelle('Sport'); // Même libellé (doublon)

        $this->em->persist($cat2); // Mise en gestion Doctrine

        // Si on faisait un flush() directement alors une exception SQL est possible
        $errors = $this->validator->validate($cat2); // Déclenchement validation

        $this->assertGreaterThan(0, count($errors)); // Vérifie qu’il y a une erreur
        $this->assertSame('Ce libellé existe déjà', $errors[0]->getMessage()); // Vérifie message exact
    }

    /**
     * 🧪 TEST 6 — Libellé unique en base de données
     */
    public function testUniqueConstraintDatabaseLevel(): void
    {
        $this->expectException(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class); // On attend exception SQL

        $cat1 = new Categorie(); // Première entité
        $cat1->setLibelle('Sport'); // Libellé
        $this->em->persist($cat1); // Persist
        $this->em->flush(); // Insert en base

        $cat2 = new Categorie(); // Deuxième entité
        $cat2->setLibelle('Sport'); // Même libellé
        $this->em->persist($cat2); // Persist
        $this->em->flush(); // Déclenche exception SQL
    }

    /**
     * 🧪 TEST 7 — Image png acceptée
     */
    public function testImagePngAccepted(): void
    {
        $filePath = __DIR__ . '/fixtures/test.png';
        // Chemin vers un fichier PNG de test placé dans un dossier fixtures

        $uploadedFile = new UploadedFile(
            $filePath,                  // Chemin réel du fichier
            'test.png',                 // Nom original simulé
            'image/png',                // Type MIME simulé (important pour le test)
            null,                       // Taille auto-détectée
            true                        // Mode test pour éviter erreur upload HTTP
        );

        $categorie = new Categorie();
        // Création d'une nouvelle entité

        $categorie->setLibelle('Categorie avec image PNG');
        // Attribution d'un libellé valide

        $categorie->setImageFichier($uploadedFile);
        // Injection du fichier PNG dans l'entité

        $errors = $this->validator->validate($categorie);
        // Lancement du validateur Symfony sur l'entité

        $this->assertCount(0, $errors);
        // Vérifie qu'aucune erreur de validation n'est générée
    }

    /**
     * 🧪 TEST 8 — Type mime image non autorisé
     */
    public function testImageMimeRefused(): void
    {
        $filePath = __DIR__ . '/fixtures/test.txt';
        // Chemin vers un faux fichier texte

        $uploadedFile = new UploadedFile(
            $filePath,                // Chemin réel
            'test.txt',               // Nom original
            'text/plain',             // Type MIME incorrect
            null,                     // Taille auto
            true                      // Mode test
        );

        $categorie = new Categorie();
        // Création entité

        $categorie->setLibelle('Categorie invalide');
        // Libellé valide

        $categorie->setImageFichier($uploadedFile);
        // Injection fichier non autorisé

        $errors = $this->validator->validate($categorie);
        // Validation Symfony

        $this->assertGreaterThan(0, count($errors));
        // Vérifie qu'au moins une erreur est générée
    }
 
}
