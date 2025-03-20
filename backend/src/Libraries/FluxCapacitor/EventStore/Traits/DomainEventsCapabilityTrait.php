<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Traits;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

trait DomainEventsCapabilityTrait
{
    private array $events = [];

    protected function raiseDomainEvents(DomainEventInterface $event): void
    {
        $this->{sprintf('apply%s', new \ReflectionClass($event)->getShortName())}($event);
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
