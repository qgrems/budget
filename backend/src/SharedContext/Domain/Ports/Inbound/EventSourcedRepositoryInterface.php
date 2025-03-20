<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Inbound;

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;

interface EventSourcedRepositoryInterface
{
    public function get(
        string $aggregateId,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): AggregateRootInterface;

    public function getByDomainEvents(
        string $aggregateId,
        array $domainEventClasses,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): \Generator;

    public function save(AggregateRootInterface $aggregate): void;

    public function saveMultiAggregate(array $aggregates): void;
}
