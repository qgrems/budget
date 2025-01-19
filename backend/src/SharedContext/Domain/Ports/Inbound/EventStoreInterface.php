<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Inbound;

interface EventStoreInterface
{
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): \Generator;

    public function loadByDomainEvents(string $uuid, array $domainEventClasses, ?\DateTimeImmutable $desiredDateTime = null): \Generator;

    public function save(array $events): void;
}
