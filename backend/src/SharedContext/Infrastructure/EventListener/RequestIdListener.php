<?php

declare(strict_types=1);

namespace App\SharedContext\Infrastructure\EventListener;

use App\SharedContext\Domain\Services\RequestIdProvider;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RequestIdListener implements EventSubscriberInterface
{
    private RequestIdProvider $requestIdProvider;

    public function __construct(RequestIdProvider $requestIdProvider)
    {
        $this->requestIdProvider = $requestIdProvider;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->requestIdProvider->requestId = $event->getRequest()->headers->get('Request-Id', '');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'kernel.request' => 'onKernelRequest',
        ];
    }
}
