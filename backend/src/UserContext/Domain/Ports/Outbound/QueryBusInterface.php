<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Outbound;

use App\UserContext\Domain\Ports\Inbound\QueryInterface;

interface QueryBusInterface
{
    public function query(QueryInterface $query): mixed;
}
