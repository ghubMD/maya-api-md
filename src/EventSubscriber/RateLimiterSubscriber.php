<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\HttpFoundation\Response;

class RateLimiterSubscriber implements EventSubscriberInterface
{
    private RateLimiterFactory $apiLimiter;

    public function __construct(RateLimiterFactory $apiLimiter)
    {
        $this->apiLimiter = $apiLimiter;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        //  On cible uniquement /api
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        //  clé = IP (simple version)
        // $clientIp = $request->getClientIp();

        // limiter par utilisateur et pas seulement par IP (plus précis)
        $clientIp = $request->headers->get('Authorization') ?? $request->getClientIp();

        $limiter = $this->apiLimiter->create($clientIp);

        if (false === $limiter->consume(1)->isAccepted()) {
            $event->setResponse(new Response(
                'Too Many Requests',
                429
            ));
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
