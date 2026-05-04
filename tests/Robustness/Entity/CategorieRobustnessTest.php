<?php

namespace App\Tests\Robustness\Entity;

use App\Entity\Categorie;                           // Entité testée
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * TESTS DE ROBUSTESSE — ENTITE Categorie
 *
 * Objectif :
 * - Tester les cas extrêmes
 * - Tester la résistance aux données invalides
 * - Tester les incohérences
 * - Tester la stabilité interne
 */
class CategorieRobustnessTest extends KernelTestCase
{
    private ValidatorInterface $validator; // Service de validation Symfony

    /**
     * Méthode exécutée avant chaque test
     */
    protected function setUp(): void
    {
        parent::setUp();                 // Appel obligatoire
        self::bootKernel();              // Démarrage complet du kernel
        $this->validator = static::getContainer()
            ->get(ValidatorInterface::class); // Récupération du validator
    }

    /**
     * 🔥 TEST 1 — Libellé extrêmement long
     */
    public function testLibelleExtremementLong(): void
    {
        $categorie = new Categorie(); // Création instance

        // Génération d'un libellé de 5000 caractères
        $categorie->setLibelle(str_repeat('A', 5000));

        // Validation
        $errors = $this->validator->validate($categorie);

        // on attend une erreur 
        $this->assertCount(1, $errors);
    }

    /**
     * 🔥 TEST 2 — Libellé avec caractères spéciaux
     */
    public function testLibelleCaracteresSpeciaux(): void
    {
        $categorie = new Categorie();

        $categorie->setLibelle("!@#$%^&*()_+-=[]{}");

        $errors = $this->validator->validate($categorie);

        $this->assertCount(0, $errors);
    }

    /**
     * 🔥 TEST 3 — Libellé avec espaces uniquement
     */
    public function testLibelleEspacesUniquement(): void
    {
        $categorie = new Categorie();

        $categorie->setLibelle("   ");

        $errors = $this->validator->validate($categorie);

        $this->assertCount(1, $errors);
    }

    /**
     * 🔥 TEST 4 — Upload fichier non image
     */
    public function testUploadFichierNonImage(): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('Test');

        // Création d'un faux fichier
        $tmpFile = tempnam(sys_get_temp_dir(), 'robust');
        file_put_contents($tmpFile, 'fake content');

        // Simule upload .exe
        $uploadedFile = new UploadedFile(
            $tmpFile,
            'document.xlsx',
            'application/octet-stream',
            null,
            true
        );

        // Injection dans entité
        $categorie->setImageFichier($uploadedFile);

        // Validation
        $errors = $this->validator->validate($categorie);

        // Si contrainte mime active → erreur attendue
        $this->assertCount(1, $errors);

        unlink($tmpFile);
    }

    /**
     * 🔥 TEST 5 — Upload d'un fichier de taille extrême
     *
     * Objectif :
     * vérifier que la contrainte Assert\File(maxSize)
     * rejette un fichier trop volumineux.
     *
     * Ici on simule un fichier dépassant la limite fixée dans l'entité.
     */
    public function testUploadFichierTailleExtreme(): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('CategorieTest');

        // Création d'un fichier temporaire très volumineux (3MB)
        $tmpFile = tempnam(sys_get_temp_dir(), 'robust');
        file_put_contents($tmpFile, str_repeat('A', 3 * 1024 * 1024));

        $uploadedFile = new UploadedFile(
            $tmpFile,
            'grosse_image.jpg',
            'image/jpeg',
            null,
            true
        );

        $categorie->setImageFichier($uploadedFile);

        $errors = $this->validator->validate($categorie);

        // On attend au moins une erreur de validation
        $this->assertGreaterThan(0, count($errors));

        unlink($tmpFile);
    }

    /**
     * 🔥 TEST 6 — Upload avec nom de fichier vide
     *
     * Objectif :
     * vérifier que l'entité ne plante pas si le nom du fichier
     * fourni par le client est vide ou incorrect.
     *
     * Ce test simule un comportement client anormal.
     */
    public function testUploadNomFichierVide(): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('CategorieTest');

        $tmpFile = tempnam(sys_get_temp_dir(), 'robust');
        file_put_contents($tmpFile, 'fake image');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            '', // nom volontairement vide
            'image/jpeg',
            null,
            true
        );

        $categorie->setImageFichier($uploadedFile);

        $errors = $this->validator->validate($categorie);

        // Le test vérifie surtout qu'aucune exception n'est levée
        $this->assertIsIterable($errors);

        unlink($tmpFile);
    }

    /**
     * 🔥 TEST 7 — Upload répété du même fichier
     *
     * Objectif :
     * vérifier la stabilité du setter lorsque l'on remplace
     * plusieurs fois le fichier image.
     *
     * Cela peut arriver lors de modifications successives
     * d'une entité dans une interface d'administration.
     */
    public function testUploadPlusieursFois(): void
    {
        $categorie = new Categorie();
        $categorie->setLibelle('CategorieTest');

        for ($i = 0; $i < 5; $i++) {

            $tmpFile = tempnam(sys_get_temp_dir(), 'robust');
            file_put_contents($tmpFile, 'fake image');

            $uploadedFile = new UploadedFile(
                $tmpFile,
                'image'.$i.'.jpg',
                'image/jpeg',
                null,
                true
            );

            $categorie->setImageFichier($uploadedFile);

            $errors = $this->validator->validate($categorie);

            // L'entité doit rester stable et valide
            $this->assertIsIterable($errors);

            unlink($tmpFile);
        }

        // Si aucune exception n'a été levée → robustesse correcte
        $this->assertTrue(true);
    }

    /**
     * 🔥 TEST 8 — Fuzzing du libellé
     *
     * Objectif :
     * vérifier la stabilité du setter et de la validation lorsque l'on fournit
     * des valeurs aléatoires et potentiellement invalides.
     *
     * Cela peut arriver lors de modifications successives
     * d'une entité dans une interface d'administration.
     */
    public function testFuzzingLibelle(): void
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()[]{}<>?/\\|';

        for ($i = 0; $i < 200; $i++) {

            $categorie = new Categorie();

            // Génération longueur aléatoire
            $length = random_int(0, 200);

            // Génération chaîne aléatoire
            $randomString = '';
            for ($j = 0; $j < $length; $j++) {
                $randomString .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }

            $categorie->setLibelle($randomString);

            // Validation
            $errors = $this->validator->validate($categorie);

            // Le test vérifie surtout l'absence d'exception
            $this->assertIsIterable($errors);
        }

        // Si aucune exception → robustesse correcte
        $this->assertTrue(true);
    }

    protected function tearDown(): void
    {
        parent::tearDown(); // Nettoyage standard
    }
}

