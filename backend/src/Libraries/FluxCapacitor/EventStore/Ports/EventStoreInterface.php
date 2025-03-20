<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Ports;

interface EventStoreInterface
{
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): AggregateRootInterface;

    public function loadByDomainEvents(string $uuid, array $domainEventClasses, ?\DateTimeImmutable $desiredDateTime = null): \Generator;

    public function save(AggregateRootInterface $aggregate): void;

    public function saveMultiAggregate(array $aggregates): void;
}
