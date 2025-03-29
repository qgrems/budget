<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\EventStore\Services;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

class RequestIdProvider
{
    public string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID;
}
