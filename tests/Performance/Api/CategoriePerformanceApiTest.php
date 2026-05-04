<?php

// Déclaration de l'espace de nom du test.
// On place les tests de performance dans un namespace dédié.
namespace App\Tests\Performance;

// Import de l'entité que l'on va manipuler dans les tests.
use App\Entity\Categorie;

// WebTestCase permet de démarrer le kernel Symfony
// et d'utiliser un client HTTP pour tester l'API.
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Symfony\Component\HttpFoundation\File\UploadedFile;

// Classe de test dédiée à la mesure de certaines performances
// de l'entité Categorie et de son API associée.
class CategoriePerformanceApiTest extends WebTestCase
{
    // Cette propriété indique à PHPUnit de garder le kernel démarré
    // entre les tests afin d'éviter un redémarrage complet à chaque méthode.
    // Cela réduit le temps global d'exécution des tests.
    protected static ?bool $alwaysBootKernel = true;

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
     * 🌐 Test de performance de l'API REST
     * 
     * Ce test mesure le temps de réponse de l'endpoint
     * GET /api/categories exposé par API Platform.
     */
    public function testApiGetPerformance(): void
    {
        // Création d'un client HTTP de test Symfony.
        // Ce client permet de simuler des requêtes HTTP
        // vers l'application sans serveur externe.
        $client = static::createClient();

        // Démarrage de la mesure du temps.
        $start = microtime(true);

        // Envoi d'une requête HTTP GET vers l'API.
        $client->request('GET', '/api/categories');

        // Calcul du temps de réponse de l'endpoint.
        $executionTime = microtime(true) - $start;

        // Affichage du temps mesuré.
        fwrite(STDOUT, "\nTemps API GET : {$executionTime} sec\n");

        // Vérification que la réponse HTTP est correcte (200 OK).
        $this->assertResponseIsSuccessful();

        // Vérification que le temps de réponse reste inférieur à 1 seconde.
        $this->assertLessThan(1, $executionTime);
    }

    /**
     * ⚡ Test de performance de la récupération d'une ressource
     *
     * Ce test mesure le temps nécessaire pour récupérer
     * une catégorie spécifique via son identifiant.
     */
    public function testApiGetItemPerformance(): void
    {
        $client = static::createClient();

        // Création préalable d'une catégorie
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $categorie = new \App\Entity\Categorie();
        $categorie->setLibelle('Categorie Test Item');

        $entityManager->persist($categorie);
        $entityManager->flush();

        // Début de la mesure du temps
        $start = microtime(true);

        // Requête GET vers l'item API
        $client->request('GET', '/api/categories/' . $categorie->getId());

        $executionTime = microtime(true) - $start;

        fwrite(STDOUT, "\nTemps API GET ITEM : {$executionTime} sec\n");

        $this->assertResponseIsSuccessful();

        $this->assertLessThan(1, $executionTime);
    }

    /**
     * 🗑 Test de performance de suppression d'une ressource
     *
     * Ce test mesure le temps nécessaire pour supprimer
     * une catégorie via l'endpoint DELETE.
     */
    public function testApiDeletePerformance(): void
    {
        $client = static::createClient();

        // Création préalable d'une catégorie à supprimer
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        $categorie = new \App\Entity\Categorie();
        $categorie->setLibelle('Categorie Delete Test');

        $entityManager->persist($categorie);
        $entityManager->flush();

        // Début de la mesure du temps
        $start = microtime(true);

        // Envoi de la requête DELETE
        $client->request('DELETE', '/api/categories/' . $categorie->getId());

        $executionTime = microtime(true) - $start;

        fwrite(STDOUT, "\nTemps API DELETE : {$executionTime} sec\n");

        // Vérification que la suppression s'est bien déroulée
        $this->assertResponseStatusCodeSame(204);

        // Vérification du seuil de performance
        $this->assertLessThan(1, $executionTime);
    }

    /**
     * 🧹 Méthode exécutée après chaque test
     *
     * tearDown() est une méthode spéciale de PHPUnit appelée
     * automatiquement après l'exécution de chaque méthode de test.
     *
     * Son rôle est de nettoyer l'environnement afin que les tests
     * suivants s'exécutent dans un contexte sain et reproductible.
     */
    protected function tearDown(): void
    {
        // Récupération de l'EntityManager via le conteneur Symfony.
        // L'EntityManager permet d'exécuter des opérations sur la base
        // de données à l'aide de Doctrine ORM.
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Exécution d'une requête DQL (Doctrine Query Language).
        // Cette requête supprime toutes les entités Categorie
        // présentes dans la base de données.
        //
        // Elle est équivalente en SQL à :
        // DELETE FROM categorie
        //
        // L'objectif est d'éviter que les données insérées
        // lors d'un test ne perturbent les tests suivants.
        $entityManager->createQuery('DELETE FROM App\Entity\Categorie')->execute();

        // Appel de la méthode tearDown() de la classe parente (WebTestCase).
        // Cela permet à PHPUnit et Symfony d'exécuter leurs propres
        // opérations de nettoyage internes après le test.
        parent::tearDown();
    }
}
