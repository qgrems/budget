<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

interface PublisherInterface
{
    public function publishDomainEvents(array $events): void;

    public function publishNotificationEvents(array $events): void;
}
