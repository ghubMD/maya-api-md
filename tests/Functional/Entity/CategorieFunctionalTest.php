<?php

// Déclaration du namespace pour les tests
namespace App\Tests;

// Base de test API Platform pour simuler des requêtes HTTP
use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;

// Entité à tester
use App\Entity\Categorie;

// Factory pour créer rapidement des catégories
use App\Factory\CategorieFactory;

// Trait Foundry pour pouvoir utiliser createOne() et createMany()
use Zenstruck\Foundry\Test\Factories;

// Pour simuler un fichier uploadé
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CategorieFunctionalTest extends ApiTestCase
{
    // ⚠️ ApiPlatform 5+ : boot kernel à chaque création de client
    // $alwaysBootKernel = true : obligatoire pour DAMA + ApiPlatform 5+
    protected static ?bool $alwaysBootKernel = true;

    // Permet de générer facilement des entités via les factories 
    // Permet d'utiliser createOne() / createMany() de Foundry
    use Factories;

    /**
     * Fonction utilitaire pour construire le chemin vers les fichiers de fixtures
     * Exemple utilisation : $this->fixturePath('test.jpg') pour obtenir le chemin complet vers tests/fixtures/files/test.jpg
     */
    protected function fixturePath(string $file): string
    {
        return static::getContainer()->getParameter('kernel.project_dir')
            . '/tests/fixtures/files/' . $file;
    }


    /**
     * Test de récupération de la collection de catégories
     */
    public function testGetCollection(): void
    {
        // 🔹 Création de 30 catégories en base de test via la factory
        CategorieFactory::createMany(30);

        // 🔹 Création d'un client HTTP pour interroger l'API
        $client = static::createClient();

        // 🔹 Requête GET sur /api/categories
        $client->request('GET', '/api/categories');

        // 🔹 Récupération de la réponse brute
        $response = $client->getResponse();

        // 🔹 Convertit le JSON en tableau PHP
        $data = $response->toArray();

        // 🔹 Vérifie que la réponse HTTP est 200
        $this->assertResponseIsSuccessful();

        // 🔹 Vérifie que la collection contient 30 éléments
        $this->assertCount(30, $data['member']);

        // 🔹 Vérifie la structure JSON-LD
        $this->assertArraySubset([
            '@context' => '/api/contexts/Categorie',
            '@id' => '/api/categories',
            '@type' => 'Collection',
        ], $data);

        // 🔹 Vérifie la conformité avec le JSON Schema de l'entité
        $this->assertMatchesResourceCollectionJsonSchema(Categorie::class);
    }

    /**
     * Test de création d'une catégorie valide avec upload
     */
    public function testCreateCategorie(): void
    {
        // 🔹 Création du client HTTP
        $client = static::createClient();

        // 🔹 Création d’un fichier temporaire pour éviter de supprimer le vrai fixture
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');

        // 🔹 Copie du vrai fichier dans le fichier temporaire
        copy($this->fixturePath('test.jpg'), $tempFile);

        // 🔹 Création d’un UploadedFile basé sur le fichier temporaire
        $uploadedFile = new UploadedFile(
            $tempFile,
            'test.jpg',
            'image/jpeg',
            null,
            true
        );

        // 🔹 POST multipart vers /api/categories avec un libellé et un fichier simulé
        $client->request('POST', '/api/categories', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'libelle' => 'Informatique 2',
                ],
                'files' => [
                    'imageFichier' => $uploadedFile,
                ],
            ],
        ]);

        // 🔹 Récupération de la réponse
        $response = $client->getResponse();

        // 🔹 Vérifie que la création retourne HTTP 201
        $this->assertResponseStatusCodeSame(201);

        // 🔹 Convertit la réponse en tableau PHP
        $data = $response->toArray();

        // 🔹 Vérifie que le libellé est correct
        $this->assertEquals('Informatique 2', $data['libelle']);

        // 🔹 Vérifie la conformité JSON Schema
        $this->assertMatchesResourceItemJsonSchema(Categorie::class);
    }

    /**
     * Test de création d'une catégorie invalide (libellé vide)
     */
    public function testCreateInvalidCategorie(): void
    {
        // 🔹 Création du client HTTP
        $client = static::createClient();

        // 🔹 POST avec libellé vide
        $client->request('POST', '/api/categories', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => ['libelle' => ''],
            ],
        ]);

        // 🔹 Récupère la réponse sans lancer d'exception 422
        $response = $client->getResponse();

        // 🔹 le ValidationExceptionListener transforme les 422 en JSON clair sans lancer l'exception
        //    toArray(false) : convertit le JSON en tableau PHP sans lancer d'exception
        $data = $response->toArray(false);

        // 🔹 Vérifie que le code HTTP est 422
        $this->assertResponseStatusCodeSame(422);

        // 🔹 Vérifie que la clé 'errors' existe
        $this->assertArrayHasKey('errors', $data);

        // 🔹 Vérifie qu'il y a deux violations pour 'libelle'
        $this->assertCount(2, $data['errors']);

        // 🔹 Vérifie le premier message (NotBlank)
        $this->assertEquals('libelle', $data['errors'][0]['property']);
        $this->assertEquals('Le libellé est obligatoire', $data['errors'][0]['message']);

        // 🔹 Vérifie le second message (Length min=3)
        $this->assertEquals('libelle', $data['errors'][1]['property']);
        $this->assertEquals(
            'Le libellé doit comporter au moins 3 caractères',
            $data['errors'][1]['message']
        );
    }

    public function testDeleteCategorie(): void
    {
        // Création de la catégorie via la factory
        CategorieFactory::createOne(['libelle' => 'A supprimer']);

        // Récupération de l’IRI de la catégorie créée
        $iri = $this->findIriBy(Categorie::class, ['libelle' => 'A supprimer']);

        // Création du client HTTP fourni par ApiTestCase
        $client = static::createClient();

        // Appel DELETE sur l’IRI de la catégorie
        $client->request('DELETE', $iri);

        // Vérifie que l’API retourne bien code HTTP 204 No Content
        $this->assertResponseStatusCodeSame(204);

        // ⚡ Vérifie que la ressource n’existe plus via l’API   → HTTP 404
        $client->request('GET', $iri);
        $this->assertResponseStatusCodeSame(404);
    }
}
