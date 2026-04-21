<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ImportFileException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onException',
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $statusCode = 500;
        $message = 'Internal server error.';

        if ($exception instanceof ImportFileException) {
            $statusCode = 400;
            $message = $exception->getMessage();
        } elseif ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
            $message = $exception->getMessage() !== '' ? $exception->getMessage() : $message;
        }

        $event->setResponse(new JsonResponse([
            'error' => [
                'message' => $message,
            ],
        ], $statusCode));
    }
}
