<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Ports\Outbound;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\QueryInterface;

interface QueryBusInterface
{
    public function query(QueryInterface $query): mixed;
}
