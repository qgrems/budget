<?php

namespace App\SharedContext\Infrastructure\Repositories;

use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

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
    public function getByDomainEvents(
        string $aggregateId,
        array $domainEventClasses,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): \Generator {
        return $this->eventStore->loadByDomainEvents($aggregateId, $domainEventClasses, $desiredDateTime);
    }

    #[\Override]
    public function save(array $raisedEvents, int $version): void
    {
        $this->eventStore->save($raisedEvents, $version);
    }
}
