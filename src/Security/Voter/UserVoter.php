<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

// Hérite de Voter pour intégrer le système de sécurité Symfony.
class UserVoter extends Voter
{
    // constante pour l'attribut de permission utilisé dans is_granted
    public const VIEW = 'USER_VIEW';

    // Injection de la classe Security pour vérifier les rôles de l'utilisateur
    //   permet d’appeler $this->security->isGranted()
    public function __construct(private Security $security) {}

    // Vérifie si ce voter doit être utilisé pour l'attribut et le sujet donnés
    // Méthode appelée en premier par Symfony.
    protected function supports(string $attribute, $subject): bool
    {
        // On cible uniquement l'attribut USER_VIEW et les sujets de type User
        return $attribute === self::VIEW && $subject instanceof User;
    }

    // Logique d'autorisation : vérifie si l'utilisateur a le droit de voir le sujet
    // Méthode appelée si supports() retourne true.
    // $attribute est la permission demandée (ici, USER_VIEW).
    // $userEntity est l'instance de User que l'on veut vérifier (le sujet)
    // $token contient l'utilisateur courant et ses rôles.
    protected function voteOnAttribute(string $attribute, $userEntity, TokenInterface $token): bool
    {
        // Récupère l'utilisateur courant à partir du token de sécurité
        $user = $token->getUser();
        // Si l'utilisateur n'est pas connecté (anonyme), on refuse l'accès
        if (!$user instanceof User) {
            return false;
        }

        // Admin peut tout voir
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // ROLE_GESTION peut voir ROLE_CLIENT
        if ($this->security->isGranted('ROLE_GESTION') &&
            in_array('ROLE_CLIENT', $userEntity->getRoles(), true)
        ) {
            return true;
        }

        // User ne voit que lui-même
        return $user === $userEntity;
    }
}
