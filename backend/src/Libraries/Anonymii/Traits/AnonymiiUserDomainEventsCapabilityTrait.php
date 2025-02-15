<?php

declare(strict_types=1);

namespace App\Libraries\Anonymii\Traits;

use App\Libraries\Anonymii\Events\AnonymiiUserDomainEventInterface;

trait AnonymiiUserDomainEventsCapabilityTrait
{
    private array $events = [];

    protected function raiseDomainEvents(AnonymiiUserDomainEventInterface $event): void
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
