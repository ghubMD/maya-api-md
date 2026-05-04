<?php

// Déclaration de l'espace de nom.
// Les tests de charge sont regroupés dans un namespace spécifique
// afin de distinguer clairement les tests unitaires,
// d'intégration et de performance.
namespace App\Tests\Performance;

// Import de la classe WebTestCase.
// Cette classe permet de démarrer le kernel Symfony
// et de simuler des requêtes HTTP vers l'API.
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Classe de test simulant un mini test de charge.
// Ce test ne remplace pas un outil spécialisé (k6, JMeter),
// mais permet de vérifier que l'API reste stable sous
// une série de requêtes répétées.
class CategorieLoadTest extends WebTestCase
{
    // Cette propriété indique à PHPUnit de conserver
    // le kernel Symfony démarré entre les méthodes de test.
    // Cela évite un redémarrage complet de l'application
    // pour chaque test et améliore les performances globales.
    protected static ?bool $alwaysBootKernel = true;

    // Définition d'une constante représentant le nombre
    // de requêtes à exécuter dans le test de charge.
    // Utiliser une constante permet de modifier facilement
    // la charge du test sans changer la logique du code.
    private const LOAD_REQUESTS = 20;

    /**
     * 🔁 Test de mini charge API (requêtes GET)
     *
     * Ce test simule une charge séquentielle sur l'API.
     * Il envoie plusieurs requêtes GET successives vers
     * l'endpoint /api/categories.
     *
     * IMPORTANT :
     * Les requêtes sont exécutées les unes après les autres
     * dans un seul processus PHPUnit.
     *
     * Il ne s'agit donc pas d'un véritable test de charge
     * concurrent comme ceux réalisés avec k6 ou JMeter.
     */
    public function testApiLoadPerformance(): void
    {
        // Création d'un client HTTP de test Symfony.
        // Ce client permet de simuler des requêtes HTTP
        // vers l'application sans serveur web externe.
        $client = static::createClient();

        // Démarrage du chronomètre global.
        // microtime(true) retourne l'heure actuelle en secondes
        // avec précision microseconde.
        $start = microtime(true);

        // Boucle simulant plusieurs requêtes successives.
        // Chaque itération représente un utilisateur fictif.
        for ($i = 0; $i < self::LOAD_REQUESTS; $i++) {

            // Démarrage du chronomètre pour la requête individuelle.
            $reqStart = microtime(true);

            // Envoi de la requête GET vers l'API.
            $client->request('GET', '/api/categories');

            // Calcul du temps de réponse de cette requête.
            $reqTime = microtime(true) - $reqStart;

            // Vérification que la réponse HTTP est correcte (200 OK).
            $this->assertResponseIsSuccessful();

            // Vérification que chaque requête reste suffisamment rapide.
            // Cela permet de détecter une éventuelle dégradation
            // progressive des performances.
            $this->assertLessThan(
                0.5,
                $reqTime,
                "La requête numéro {$i} est trop lente"
            );
        }

        // Calcul du temps total d'exécution du test.
        $executionTime = microtime(true) - $start;

        // Calcul du temps moyen par requête.
        $averageTime = $executionTime / self::LOAD_REQUESTS;

        // Affichage des résultats dans la console PHPUnit.
        fwrite(STDOUT, "\nTemps total " . self::LOAD_REQUESTS . " requêtes API : {$executionTime} sec\n");
        fwrite(STDOUT, "Temps moyen par requête : {$averageTime} sec\n");

        // Vérification que le temps global reste raisonnable.
        // Ce seuil dépend fortement de l'environnement
        // (machine locale, CI/CD, conteneur Docker...).
        $this->assertLessThan(3, $executionTime);
    }


    /**
     * 🧹 Nettoyage après chaque test
     *
     * Cette méthode est exécutée automatiquement par PHPUnit
     * après chaque méthode de test.
     *
     * Son rôle est de supprimer les données générées
     * pendant les tests afin de garantir un environnement
     * propre et reproductible.
     */
    protected function tearDown(): void
    {
        // Récupération de l'EntityManager via le conteneur Symfony.
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Exécution d'une requête DQL supprimant toutes
        // les entités Categorie de la base de données.
        $entityManager->createQuery('DELETE FROM App\Entity\Categorie')->execute();

        // Appel du tearDown() de la classe parente.
        parent::tearDown();
    }
}

