<?php

// Déclaration de l'espace de nom du test.
// On place les tests de performance dans un namespace dédié.
namespace App\Tests\Performance;

// Import de l'entité que l'on va manipuler dans les tests.
use App\Entity\Categorie;

// WebTestCase permet de démarrer le kernel Symfony
// et d'utiliser un client HTTP pour tester l'API.
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

// Classe de test dédiée à la mesure de certaines performances
// de l'entité Categorie et de son API associée.
class CategoriePerformanceTest extends WebTestCase
{
    // Cette propriété indique à PHPUnit de garder le kernel démarré
    // entre les tests afin d'éviter un redémarrage complet à chaque méthode.
    // Cela réduit le temps global d'exécution des tests.
    protected static ?bool $alwaysBootKernel = true;

    /**
     * 🚀 Test de performance d'insertion massive
     * 
     * Ce test mesure le temps nécessaire pour insérer
     * un grand nombre d'entités dans la base de données.
     * 
     * Objectif pédagogique :
     * - observer le comportement de Doctrine lors d'insertions répétées
     * - détecter une éventuelle régression de performance
     */
    public function testBulkInsertPerformance(): void
    {
        // Récupération de l'EntityManager via le conteneur Symfony.
        // L'EntityManager permet de gérer les opérations Doctrine
        // (persist, flush, requêtes, etc.).
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // microtime(true) renvoie le timestamp actuel en secondes
        // avec précision microseconde.
        // On l'utilise ici pour mesurer la durée d'exécution du test.
        $start = microtime(true);

        // Boucle simulant l'insertion d'un grand nombre de catégories.
        // Ici 500 insertions sont réalisées.
        for ($i = 0; $i < 500; $i++) {

            // Création d'une nouvelle entité Categorie.
            $categorie = new Categorie();

            // Attribution d'un libellé unique afin d'éviter
            // la contrainte d'unicité sur le champ libelle.
            $categorie->setLibelle('Performance Cat ' . $i);

            // persist() indique à Doctrine que l'entité doit être insérée
            // dans la base de données lors du prochain flush.
            $entityManager->persist($categorie);

            // on vide l'EntityManager tous les 50 inserts pour éviter la surcharge mémoire 
            //   et simuler une insertion plus réaliste
            if ($i % 50 === 0) {
                $entityManager->flush();   // exécute les requêtes SQL en attente
                $entityManager->clear();   // détache toutes les entités pour libérer la mémoire et éviter les ralentissements liés à un grand nombre d'entités gérées en même temps
            }
        }

        // flush() exécute réellement toutes les requêtes SQL nécessaires
        // pour synchroniser les entités avec la base de données.
        $entityManager->flush();

        // Calcul du temps total d'exécution du bloc testé.
        $executionTime = microtime(true) - $start;

        // Écriture du temps mesuré dans la sortie console.
        // Cela permet de suivre les performances lors de l'exécution des tests.
        fwrite(STDOUT, "\nTemps insertion 500 catégories : {$executionTime} sec\n");

        // Assertion PHPUnit :
        // on vérifie que le temps d'exécution reste inférieur à 2 secondes.
        // Ce seuil est arbitraire et dépend de l'environnement.
        $this->assertLessThan(2, $executionTime);
    }

    /**
     * ⚡ Test de performance d'une lecture repository
     * 
     * Ce test mesure la rapidité d'une requête Doctrine classique.
     */
    public function testRepositoryFindAllPerformance(): void
    {
        // Récupération de l'EntityManager.
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Récupération du repository associé à l'entité Categorie.
        // Le repository est responsable des requêtes vers la base.
        $repository = $entityManager->getRepository(Categorie::class);

        // Démarrage du chronomètre.
        $start = microtime(true);

        // Exécution de la requête Doctrine findAll()
        // qui récupère toutes les catégories.
        $repository->findAll();

        // Calcul du temps d'exécution.
        $executionTime = microtime(true) - $start;

        // Affichage du temps dans la console.
        fwrite(STDOUT, "\nTemps findAll() : {$executionTime} sec\n");

        // Vérification que la requête reste rapide.
        // Ici on attend un temps inférieur à 1 seconde.
        $this->assertLessThan(1, $executionTime);
    }

    /**
     * ⚡ Test de performance d'une requête repository avec tri
     *
     * Ce test mesure le temps nécessaire pour exécuter une requête Doctrine
     * récupérant toutes les catégories en appliquant un tri sur le champ libelle.
     *
     * Objectifs pédagogiques :
     * - observer les performances d'une requête Doctrine
     * - mesurer l'impact d'un tri (ORDER BY) sur une requête
     * - vérifier qu'une requête simple reste rapide
     */
    public function testRepositoryOrderPerformance(): void
    {
        // Récupération de l'EntityManager via le conteneur de services Symfony.
        // L'EntityManager est responsable de la gestion des entités
        // et de la communication avec la base de données.
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Récupération du repository associé à l'entité Categorie.
        // Le repository permet d'exécuter des requêtes vers la base de données.
        $repository = $entityManager->getRepository(Categorie::class);

        // Démarrage de la mesure du temps d'exécution de la requête.
        // microtime(true) retourne l'heure actuelle avec précision microseconde.
        $start = microtime(true);

        // Exécution de la méthode findBy() du repository.
        //
        // Premier argument : critères de recherche.
        // Ici [] signifie "aucun filtre", donc toutes les catégories sont récupérées.
        //
        // Deuxième argument : tableau de tri.
        // ['libelle' => 'ASC'] signifie que les résultats sont triés
        // par ordre alphabétique croissant sur le champ libelle.
        //
        // Cette instruction génère en SQL une requête de type :
        // SELECT * FROM categorie ORDER BY libelle ASC
        $repository->findBy([], ['libelle' => 'ASC']);

        // Calcul du temps d'exécution total de la requête.
        $executionTime = microtime(true) - $start;

        // Affichage du temps mesuré dans la console PHPUnit.
        // Cela permet d'observer l'évolution des performances
        // au fil des exécutions du test.
        fwrite(STDOUT, "\nTemps findBy ORDER : {$executionTime} sec\n");

        // Assertion PHPUnit :
        // on vérifie que le temps d'exécution reste inférieur à 1 seconde.
        // Ce seuil est indicatif et peut varier selon :
        // - la machine utilisée
        // - le volume de données
        // - la configuration de la base de données.
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
