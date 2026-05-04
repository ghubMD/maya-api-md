<?php

namespace App\Tests\Robustness\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;
use Zenstruck\Foundry\Test\Factories;

use App\Factory\CategorieFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CategorieRobustnessApiTest extends ApiTestCase
{
    use ResetDatabase;
    use Factories;

    protected static ?bool $alwaysBootKernel = true;

    /**
     * 🔥 TEST 1 — Libellé extrêmement long
     *
     * On vérifie que l'API refuse un libellé anormalement long.
     * Ce type d'entrée peut révéler :
     * - des problèmes de validation
     * - des dépassements de taille en base
     * - ou des erreurs serveur.
     *
     * L'API doit rejeter proprement la requête avec un code 422
     * (erreur de validation des données).
     */
    public function testCreateCategorieLibelleExtremementLong(): void
    {
        $client = static::createClient();

        $libelle = str_repeat('A', 5000);

        $client->request('POST', '/api/categories', [
            'headers' => [
                'Content-Type' => 'multipart/form-data', 
            ],
            'extra' => [
                'parameters' => [
                    'libelle' => $libelle,
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * 🔥 TEST 2 — Libellé null (champ absent)
     *
     * Vérifie la robustesse lorsque le client
     * n'envoie pas un champ obligatoire.
     */
    public function testCreateCategorieLibelleAbsent(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/categories', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'body' => []
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * 🔥 TEST 3 — Libellé composé uniquement d'espaces
     */
    public function testCreateCategorieLibelleEspaces(): void
    {
        $client = static::createClient();

        $client->request('POST', '/api/categories', [
            'headers' => [
                'Content-Type' => 'multipart/form-data',  
            ],
            'extra' => [
                'parameters' => [
                    'libelle' => '     ',
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * 🔥 TEST 4 — Création massive de catégories
     *
     * Vérifie la stabilité de l'API lorsque
     * plusieurs requêtes POST sont envoyées.
     */
    public function testCreationMassiveCategorie(): void
    {
        $client = static::createClient();

        for ($i = 0; $i < 20; $i++) {
            $client->request('POST', '/api/categories', [
                'headers' => [
                    'Content-Type' => 'multipart/form-data', 
                ],
                'extra' => [
                    'parameters' => [
                        'libelle' => 'Categorie_' . $i,
                    ],
                ],
            ]);

            $this->assertResponseStatusCodeSame(201);
        }
    }

    /**
     * 🔥 TEST 5 — Suppression répétée
     *
     * Objectif :
     * Vérifier que l'API gère correctement la suppression répétée
     * d'une même ressource.
     *
     * Scénario :
     * 1. Création d'une catégorie
     * 2. Suppression → réponse attendue : 204
     * 3. Nouvelle suppression de la même ressource
     *
     * Résultat attendu :
     * L'API doit répondre 404 car la ressource n'existe plus.
     *
     * Ce test vérifie la robustesse de l'API face à des requêtes
     * répétées ou incohérentes du client.
     */
    public function testDeleteCategoriePlusieursFois(): void
    {
        $client = static::createClient();

        $categorie = CategorieFactory::createOne();
        $iri = '/api/categories/' . $categorie->getId();

        // Suppression normale
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(204);

        // Suppression d'une ressource déjà supprimée
        $client->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * 🔥 TEST 6 — GET d'une ressource inexistante
     */
    public function testGetCategorieInexistante(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/categories/999999');

        $this->assertResponseStatusCodeSame(404);
    }

    /**
     * 🔥 TEST 7 — Upload d'un fichier non image
     *
     * Objectif pédagogique :
     * vérifier que l'API rejette un fichier dont le type
     * ne correspond pas aux formats autorisés.
     *
     * Ici on simule l'envoi d'un fichier texte alors que
     * l'entité attend une image JPEG ou PNG.
     *
     * Résultat attendu :
     * la validation Symfony doit détecter l'erreur et
     * l'API doit retourner le code HTTP 422 (Unprocessable Entity).
     */
    public function testUploadFichierNonImage(): void
    {
        $client = static::createClient();

        $file = tempnam(sys_get_temp_dir(), 'robust');
        file_put_contents($file, 'fake content');
        $uploaded = new UploadedFile($file, 'test.txt', 'image/jpeg', null, true);

        $client->request('POST', '/api/categories', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'libelle' => 'CategorieTest',
                ],
                'files' => [
                    'imageFichier' => $uploaded
                ],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    /**
     * 🔥 TEST 8— Upload d'un fichier de taille extrême
     *
     * Objectif pédagogique :
     * vérifier que la contrainte Assert\File(maxSize)
     * empêche l'envoi de fichiers trop volumineux.
     *
     * On simule ici un fichier dépassant la limite
     * définie dans l'entité (par exemple 2MB).
     *
     * Résultat attendu :
     * l'API doit refuser la requête et renvoyer
     * une erreur de validation (HTTP 422).
     */
    public function testUploadFichierTailleExtreme(): void
    {
        $client = static::createClient();

        $file = tempnam(sys_get_temp_dir(), 'robust');
        file_put_contents($file, str_repeat('A', 5 * 1024 * 1024));
        $uploaded = new UploadedFile($file, 'test.jpg', 'image/jpeg', null, true);

        $client->request('POST', '/api/categories', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'libelle' => 'CategorieTest',
                ],
                'files' => [
                    'imageFichier' => $uploaded
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        unlink($file);
    }

/**
     * 🔥 TEST 9— Upload avec nom de fichier vide
     *
     * Objectif pédagogique :
     * vérifier que l'API reste stable si un client
     * envoie un fichier dont le nom est vide.
     *
     * Ce cas peut se produire avec certains clients HTTP
     * ou implémentations incorrectes du multipart.
     *
     * Résultat attendu :
     * l'API ne doit pas planter et doit traiter la requête
     * normalement si le contenu du fichier est valide.
     */
    public function testUploadNomFichierVide(): void
    {
        $client = static::createClient();

        $file = tempnam(sys_get_temp_dir(), 'robust');
        file_put_contents($file, 'fake image');

        $uploaded = new \Symfony\Component\HttpFoundation\File\UploadedFile(
            $file,
            '',
            'image/jpeg',
            null,
            true
        );

        $client->request('POST', '/api/categories', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'libelle' => 'CategorieTest',
                ],
                'files' => [
                    'imageFichier' => $uploaded
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(422);
        unlink($file);
    }

    /**
     * 🔥 TEST 10 — Upload avec nom de fichier extrêmement long
     *
     * Objectif pédagogique :
     * vérifier la robustesse du système face à des
     * métadonnées inhabituelles provenant du client.
     *
     * Certains systèmes peuvent envoyer des noms de
     * fichiers très longs, ce qui peut provoquer
     * des erreurs dans le stockage ou le traitement.
     *
     * Résultat attendu :
     * l'API doit rester stable et traiter la requête
     * sans provoquer d'erreur serveur.
     */
    public function testUploadNomFichierTresLong(): void
    {
        $client = static::createClient();

        $tmp = tempnam(sys_get_temp_dir(), 'img');

        $image = imagecreatetruecolor(10, 10);
        imagejpeg($image, $tmp);
        imagedestroy($image);

        $longName = str_repeat('A', 600) . '.jpg';
        $file = new UploadedFile(
            $tmp,
            $longName,
            'image/jpeg',
            null,
            true
        );

        $client->request('POST', '/api/categories', [
            'headers' => ['Content-Type' => 'multipart/form-data'],
            'extra' => [
                'parameters' => [
                    'libelle' => 'CategorieTest',
                ],
                'files' => [
                    'imageFichier' => $file
                ]
            ]
        ]);

        $this->assertResponseStatusCodeSame(201);
    }

    /**
     * Méthode exécutée automatiquement après chaque test PHPUnit.
     *
     * Objectif :
     * Nettoyer le dossier d'upload utilisé pendant les tests afin
     * d'éviter que les fichiers créés par un test influencent les tests suivants.
     *
     * Cette méthode garantit l'isolation des tests : chaque test démarre
     * avec un environnement propre.
     */
    protected function tearDown(): void
    {
        // On récupère le chemin racine du projet Symfony grâce au paramètre
        // "kernel.project_dir" fourni par le conteneur de services.
        // Cela permet d'obtenir un chemin fiable, indépendant de l'emplacement
        // du fichier de test dans l'arborescence du projet.
        $dir = static::getContainer()->getParameter('kernel.project_dir') . '/var/test_uploads';

        // On vérifie que le dossier existe réellement.
        // Cette vérification évite une erreur PHP si le dossier n'a pas encore
        // été créé ou si aucun fichier n'a été uploadé pendant le test.
        if (is_dir($dir)) {

            // La fonction glob() permet de récupérer la liste des fichiers
            // présents dans le dossier. L'expression '/*' signifie :
            // "tous les fichiers situés directement dans ce dossier".
            foreach (glob($dir . '/*') as $file) {

                // La fonction unlink() supprime le fichier du système de fichiers.
                // Chaque fichier uploadé pendant les tests est donc supprimé.
                // Cela garantit que le dossier sera vide pour le prochain test.
                unlink($file);
            }
        }
    }



}
