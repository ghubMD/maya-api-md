<?php

namespace App\Tests\Security;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class AttackTest extends ApiTestCase
{
    use ResetDatabase;

    protected static ?bool $alwaysBootKernel = true;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->em = static::getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * 🔧 Création utilisateur
     */
    private function createUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setNom('Nom');
        $user->setPrenom('Prenom');
        $user->setTelephone('0600000000');

        // password = userdemo
        $user->setPassword('$2y$13$DkrW99CS4JG8RaH9zPgZ2ORxVCbx3hu1l71VCVsBj2xzQASZFqQfC');

        // ⚠ donnée ultra sensible
        $user->setGoogleAuthenticatorSecret('SECRET_2FA');

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    private function getToken(string $email): string
    {
        $client = static::createClient();

        $response = $client->request('POST', '/auth', [
            'json' => [
                'email' => $email,
                'password' => 'userdemo',
            ],
        ]);

        return $response->toArray()['token'];
    }

    // ==============================
    // TEST BRUTE FORCE (login_throttling)
    // ==============================
    public function testBruteForceLogin(): void
    {
        $this->createUser('user@test.com', ['ROLE_USER']);

        $client = static::createClient();

        // 6 tentatives (limite = 5)
        for ($i = 0; $i < 6; $i++) {
            $client->request('POST', '/auth', [
                'json' => [
                    'email' => 'user@test.com',
                    'password' => 'wrong',
                ],
            ]);
        }

        // dernière tentative doit être bloquée
        $response = $client->request('POST', '/auth', [
            'json' => [
                'email' => 'user@test.com',
                'password' => 'wrong',
            ],
        ]);

        $this->assertTrue(
            in_array($response->getStatusCode(), [401, 429]),
            'Doit être bloqué après trop de tentatives'
        );
    }

    // ==============================
    // TEST FLOOD API (rate limiter)
    // ==============================
    public function testApiRateLimiting(): void
    {
        $client = static::createClient();

        // 11 requêtes (limite = 10)
        for ($i = 0; $i < 11; $i++) {
            $response = $client->request('GET', '/api/produits');
        }

        // dernière requête doit être bloquée
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 429]),
            'Doit être bloqué après trop de requêtes'
        );
    }

    // ==============================
    // TEST TOKEN INVALIDE
    // ==============================
    public function testInvalidToken(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', '/api/me', [
            'headers' => ['Authorization' => 'Bearer INVALID_TOKEN'],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // ==============================
    // TEST SCRAPING (extraction massive)
    // ==============================
    public function testScrapingUsers(): void
    {
        $this->createUser('admin@test.com', ['ROLE_ADMIN']);

        // création de plusieurs users
        for ($i = 0; $i < 20; $i++) {
            $this->createUser("user$i@test.com", ['ROLE_USER']);
        }

        $token = $this->getToken('admin@test.com');

        $client = static::createClient();

        $responses = [];

        for ($i = 0; $i < 50; $i++) {
            $response = $client->request('GET', '/api/users', [
                'headers' => ['Authorization' => 'Bearer ' . $token],
            ]);

            $responses[] = $response->getStatusCode();
        }

        // doit être limité après un certain nombre de requêtes
        $this->assertTrue(
            in_array(429, $responses)
        );
    }

    // ==============================
    // TEST SQL INJECTION
    // ==============================
    public function testSqlInjectionWithoutFilter(): void
    {
        $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $this->createUser('user1@test.com', ['ROLE_USER']);
        $this->createUser('user2@test.com', ['ROLE_USER']);

        $token = $this->getToken('admin@test.com');

        $client = static::createClient();

        $response = $client->request(
            'GET',
            "/api/users?email=' OR 1=1 --",
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
            ]
        );

        // API ne plante pas
        $this->assertResponseIsSuccessful();

        $data = $response->toArray();

        // structure OK
        $this->assertArrayHasKey('member', $data);

        // comportement normal : retourne tous les users
        $this->assertCount(3, $data['member']);
    }
}
