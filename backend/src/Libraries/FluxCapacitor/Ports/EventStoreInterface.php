<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Ports;

interface EventStoreInterface
{
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): \Generator;

    public function loadByDomainEvents(string $uuid, array $domainEventClasses, ?\DateTimeImmutable $desiredDateTime = null): \Generator;

    public function save(array $events, int $version): void;

    public function getCurrentVersion(string $aggregateId): int;
}
