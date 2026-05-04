<?php

// Déclaration du namespace pour l’Event Listener
namespace App\EventListener;

// On importe l’exception de validation API Platform
use ApiPlatform\Validator\Exception\ValidationException;

// Symfony fournit cet objet qui encapsule les exceptions du kernel
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

// Pour renvoyer une réponse HTTP JSON
use Symfony\Component\HttpFoundation\JsonResponse;

class ValidationExceptionListener
{
    /**
     * Méthode appelée à chaque exception interceptée par le kernel
     *
     * @param ExceptionEvent $event L’événement contenant l’exception
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        // 🔹 Récupération de l’exception qui vient d’être levée
        $exception = $event->getThrowable();

        // 🔹 Vérifie si c’est une ValidationException de API Platform
        if ($exception instanceof ValidationException) {

            // 🔹 Tableau pour stocker les erreurs sous forme lisible
            $violations = [];

            // 🔹 La ValidationException contient un ConstraintViolationList de Symfony
            //     Chaque violation correspond à une contrainte qui a échoué sur l’entité
            foreach ($exception->getConstraintViolationList() as $violation) {

                // 🔹 On extrait la propriété et le message de la violation
                $violations[] = [
                    'property' => $violation->getPropertyPath(), // ex: "libelle"
                    'message' => $violation->getMessage(),       // ex: "Le libellé est obligatoire"
                ];
            }

            // 🔹 Création d’une réponse JSON avec le tableau des violations
            //     et code HTTP 422 (Unprocessable Entity)
            $response = new JsonResponse([
                'errors' => $violations
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);

            // 🔹 On “remplace” la réponse par défaut du kernel par notre réponse JSON
            $event->setResponse($response);

            // 🔹 À partir de là, la réponse HTTP sera envoyée au client au lieu de l’exception brute
        }
    }
}

