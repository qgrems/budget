<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Traits;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

trait EventsCapabilityTrait
{
    private array $events = [];

    protected function raise(EventInterface $event): void
    {
        $this->events[] = $event;
    }

    public function raisedEvents(): array
    {
        return $this->events;
    }

    public function clearRaisedEvents(): array
    {
        return $this->events = [];
    }
}
