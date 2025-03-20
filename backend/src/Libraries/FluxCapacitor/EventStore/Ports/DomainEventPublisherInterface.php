<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface DomainEventPublisherInterface
{
    public function publishDomainEvents(array $events): void;
}
