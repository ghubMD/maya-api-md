<?php

namespace App\Tests\Security;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use Zenstruck\Foundry\Test\ResetDatabase;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

class DataProtectionTest extends ApiTestCase
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
    // TESTS PROTECTION DONNÉES
    // ==============================

    public function testUserDataDoesNotExposeSensitiveFields(): void
    {
        $user = $this->createUser('user@test.com', ['ROLE_USER']);
        $token = $this->getToken('user@test.com');

        $client = static::createClient();

        $response = $client->request('GET', '/api/users/'.$user->getId(), [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        $data = $response->toArray();

        // champs autorisés
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('nom', $data);
        $this->assertArrayHasKey('prenom', $data);

        // champs  ABSENTS
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('roles', $data);
        $this->assertArrayNotHasKey('googleAuthenticatorSecret', $data);
    }

    public function testApiMeDoesNotExposeSensitiveFields(): void
    {
        $this->createUser('me@test.com', ['ROLE_USER']);
        $token = $this->getToken('me@test.com');

        $client = static::createClient();

        $response = $client->request('GET', '/api/me', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        $data = $response->toArray();

        // jamais exposés
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('roles', $data);
        $this->assertArrayNotHasKey('googleAuthenticatorSecret', $data);
    }

    public function testAdminCollectionDoesNotExposeSensitiveFields(): void
    {
        $this->createUser('admin@test.com', ['ROLE_ADMIN']);
        $this->createUser('user@test.com', ['ROLE_USER']);

        $token = $this->getToken('admin@test.com');

        $client = static::createClient();

        $response = $client->request('GET', '/api/users', [
            'headers' => ['Authorization' => 'Bearer '.$token],
        ]);

        $data = $response->toArray();

        foreach ($data['member'] as $userData) {
            $this->assertArrayNotHasKey('password', $userData);
            $this->assertArrayNotHasKey('roles', $userData);
            $this->assertArrayNotHasKey('googleAuthenticatorSecret', $userData);
        }
    }
}