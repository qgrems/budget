<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

use App\SharedContext\Domain\Ports\Inbound\QueryInterface;

interface QueryBusInterface
{
    public function query(QueryInterface $query): mixed;
}
