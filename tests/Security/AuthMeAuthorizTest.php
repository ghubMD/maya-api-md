<?php

namespace App\Tests\Security;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

/**
 * Tests  de sécurité API :
 * - Authentification  JWT  :  cas login OK / KO
 * - /api/me   : cas avec / sans token
 * - Accès protégés : ROLE_USER vs ROLE_ADMIN
 * - Voter : ROLE_GESTION peut voir clients, ROLE_USER ne peut pas voir autres users
 * - endpoint public : /api/produits accessible sans auth
 */

class AuthMeAuthorizTest extends ApiTestCase
{
    use ResetDatabase; // reset auto DB

    // Obligatoire pour ApiPlatform 5+ et DAMA Fixtures
    protected static ?bool $alwaysBootKernel = true;

    private EntityManagerInterface $em;

    // setup exécuté avant chaque test
    protected function setUp(): void
    {
        parent::setUp();
        // récupération propre de Doctrine
        $this->em = static::getContainer()
            ->get('doctrine')
            ->getManager();
    }

    /**
     * Création utilisateur avec mot de passe déjà hashé
     */
    private function createUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setNom('Test');
        $user->setPrenom('User');

        // mot de passe déjà hashé
        $user->setPassword('$2y$13$DkrW99CS4JG8RaH9zPgZ2ORxVCbx3hu1l71VCVsBj2xzQASZFqQfC');
        // Persistance en base
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * Récupération JWT
     */
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
    // AUTHENTIFICATION
    // ==============================
    // Auth JWT  :  cas login OK  
    public function testLoginSuccess(): void
    {
        $this->createUser('user@test.com', ['ROLE_USER']);

        $token = $this->getToken('user@test.com');

        $this->assertNotEmpty($token);
    }
    //Auth JWT  :  cas login  KO
    public function testLoginFailWrongPassword(): void
    {
        $this->createUser('user@test.com', ['ROLE_USER']);

        $client = static::createClient();

        $client->request('POST', '/auth', [
            'json' => [
                'email' => 'user@test.com',
                'password' => 'wrong',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    // ==============================
    //  /api/me
    // ==============================
    //  cas avec token
    public function testApiMeSuccess(): void
    {
        $this->createUser('me@test.com', ['ROLE_USER']);
        $token = $this->getToken('me@test.com');

        $client = static::createClient();

        $response = $client->request('GET', '/api/me', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('me@test.com', $response->toArray()['email']);
    }
    // cas sans token
    public function testApiMeWithoutToken(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    // ==============================
    // ACCÈS PROTÉGÉS
    // ==============================
    //  ROLE_USER  pas accès à api/users
    public function testUserCannotAccessAdminRoute(): void
    {
        $this->createUser('user@test.com', ['ROLE_USER']);
        $token = $this->getToken('user@test.com');

        $client = static::createClient();

        $client->request('GET', '/api/users', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }
    //  ROLE_ADMIN a accès à api/users
    public function testAdminCanAccessAdminRoute(): void
    {
        $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $token = $this->getToken('admin@test.com');

        $client = static::createClient();

        $response = $client->request('GET', '/api/users', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
    }

    // ==============================
    // VOTER
    // ==============================
    //  ROLE_GESTION peut voir clients
    public function testGestionCanViewClient(): void
    {
        $this->createUser('gestion@test.com', ['ROLE_GESTION']);
        $clientUser = $this->createUser('client@test.com', ['ROLE_CLIENT']);

        $token = $this->getToken('gestion@test.com');

        $client = static::createClient();

        $response = $client->request('GET', '/api/users/' . $clientUser->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseIsSuccessful();
    }

    //  ROLE_USER ne peut pas voir autres users
    public function testUserCannotViewOtherUser(): void
    {
        $this->createUser('user1@test.com', ['ROLE_USER']);
        $otherUser = $this->createUser('user2@test.com', ['ROLE_USER']);

        $token = $this->getToken('user1@test.com');

        $client = static::createClient();

        $client->request('GET', '/api/users/' . $otherUser->getId(), [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    // ==============================
    // PUBLIC
    // ==============================
    //  endpoint public : /api/produits accessible sans auth
    public function testPublicEndpoint(): void
    {
        $client = static::createClient();

        $response = $client->request('GET', '/api/produits');

        $this->assertResponseIsSuccessful();
    }

}
