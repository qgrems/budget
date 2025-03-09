<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Traits;

use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;

trait DomainEventsCapabilityTrait
{
    private array $events = [];

    protected function raiseDomainEvents(DomainEventInterface $event): void
    {
        $this->events[] = $event;
    }

    public function raisedDomainEvents(): array
    {
        return $this->events;
    }

    public function clearRaisedDomainEvents(): array
    {
        return $this->events = [];
    }
}
