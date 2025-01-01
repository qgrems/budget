<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

interface PublisherInterface
{
    public function publishEvents(array $events): void;
}
