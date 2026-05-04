<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

class MeController extends AbstractController
{
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json([
                'message' => 'Utilisateur non authentifié'
            ], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'telephone' => $user->getTelephone(),
            'rue' => $user->getRue(),
            'codePostal' => $user->getCodePostal(),
            'ville' => $user->getVille(),
        ]);
    }
}










// namespace App\Controller;

// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use App\Dto\MeDto;
// use App\Entity\User;

// class MeController extends AbstractController
// {
//     public function __invoke(): MeDto
//     {
//         /** @var User $user */
//         $user = $this->getUser();

//         $dto = new MeDto();
//         $dto->email = $user->getEmail();
//         $dto->nom = $user->getNom();
//         $dto->prenom = $user->getPrenom();

//         return $dto;
//     }
// }









// namespace App\Controller;

// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use App\Entity\User;

// class MeController extends AbstractController
// {
//     public function __invoke(): User
//     {
//         /** @var User $user */
//         $user = $this->getUser();
//         return $user;
//     }
// }




// namespace App\Controller;

// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use App\Entity\User;
// // use Symfony\Component\HttpFoundation\JsonResponse;
// // use Symfony\Component\Routing\Attribute\Route;

// class MeController extends AbstractController
// {
//     // #[Route('/api/me', name: 'api_me', methods: ['GET'])]
//     public function __invoke():User
//     // public function __invoke(): JsonResponse
//     {
//         return $this->getUser();

//         // $user = $this->getUser();

//         // return $this->json(
//         //     $user,
//         //     200,
//         //     [],
//         //     ['groups' => ['user:read']]
//         // );
//     }
// }
