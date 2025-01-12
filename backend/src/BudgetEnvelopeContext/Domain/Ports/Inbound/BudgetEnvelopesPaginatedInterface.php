<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

interface BudgetEnvelopesPaginatedInterface
{
    public function jsonSerialize(): array;
}
