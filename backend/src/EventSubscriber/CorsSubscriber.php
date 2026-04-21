<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class CorsSubscriber implements EventSubscriberInterface
{
    public function __construct(private string $corsAllowOrigin)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 250],
            KernelEvents::RESPONSE => 'onResponse',
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getMethod() !== 'OPTIONS' || !str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->applyHeaders($response, $request->headers->get('Origin'));
        $event->setResponse($response);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $this->applyHeaders($event->getResponse(), $request->headers->get('Origin'));
    }

    private function applyHeaders(Response $response, ?string $origin): void
    {
        if ($origin === null || preg_match(sprintf('#%s#', $this->corsAllowOrigin), $origin) !== 1) {
            return;
        }

        $response->headers->set('Access-Control-Allow-Origin', $origin);
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
}
