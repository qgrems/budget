<?php

namespace App\SharedContext\Infrastructure\Repositories;

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class EventSourcedRepository implements EventSourcedRepositoryInterface
{
    public function __construct(private EventStoreInterface $eventStore)
    {
    }

    #[\Override]
    public function get(string $aggregateId, ?\DateTimeImmutable $desiredDateTime = null): AggregateRootInterface
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
    public function save(AggregateRootInterface $aggregate): void
    {
        $this->eventStore->save($aggregate);
    }

    #[\Override]
    public function saveMultiAggregate(array $aggregates): void
    {
        $this->eventStore->saveMultiAggregate($aggregates);
    }
}
