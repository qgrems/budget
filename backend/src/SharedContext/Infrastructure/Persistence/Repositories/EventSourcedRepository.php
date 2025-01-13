<?php

namespace App\SharedContext\Infrastructure\Persistence\Repositories;

use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;

final readonly class EventSourcedRepository implements EventSourcedRepositoryInterface
{
    public function __construct(private EventStoreInterface $eventStore)
    {
    }

    #[\Override]
    public function get(string $aggregateId, ?\DateTimeImmutable $desiredDateTime = null): \Generator
    {
        return $this->eventStore->load($aggregateId, $desiredDateTime);
    }

    #[\Override]
    public function save(array $raisedEvents): void
    {
        $this->eventStore->save($raisedEvents);
    }
}
