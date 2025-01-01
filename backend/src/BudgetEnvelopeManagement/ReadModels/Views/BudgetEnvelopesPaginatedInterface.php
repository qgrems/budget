<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\ReadModels\Views;

interface BudgetEnvelopesPaginatedInterface
{
    /**
     * @return iterable<int, BudgetEnvelopeViewInterface>
     */
    public function getEnvelopes(): iterable;

    public function getTotalItems(): int;
}
