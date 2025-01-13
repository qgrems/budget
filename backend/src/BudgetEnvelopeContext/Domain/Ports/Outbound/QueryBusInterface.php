<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Outbound;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\QueryInterface;

interface QueryBusInterface
{
    public function query(QueryInterface $query): mixed;
}
