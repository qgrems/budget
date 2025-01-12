<?php

namespace App\SharedContext\Domain\Ports\Inbound;

interface EventSourcedRepositoryInterface
{
    public function get(string $aggregateId, ?\DateTimeImmutable $desiredDateTime = null): \Generator;

    public function save(array $raisedEvents): void;
}
