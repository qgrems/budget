<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Inbound;

interface EventSourcedRepositoryInterface
{
    public function get(
        string $aggregateId,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): \Generator;

    public function getByDomainEvents(
        string $aggregateId,
        array $domainEventClasses,
        ?\DateTimeImmutable $desiredDateTime = null,
    ): \Generator;

    public function save(array $raisedEvents): void;
}
