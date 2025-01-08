<?php

declare(strict_types=1);

namespace App\SharedContext\EventStore;

interface EventStoreInterface
{
    public function load(string $uuid): \Generator;

    public function save(array $events): void;
}
