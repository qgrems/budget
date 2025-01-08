<?php

declare(strict_types=1);

namespace App\SharedContext\Traits;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

trait EventsCapability
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
