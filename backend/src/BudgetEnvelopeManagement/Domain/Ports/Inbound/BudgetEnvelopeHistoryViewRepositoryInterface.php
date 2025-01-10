<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Ports\Inbound;

interface BudgetEnvelopeHistoryViewRepositoryInterface
{
    public function save(BudgetEnvelopeHistoryViewInterface $budgetEnvelopeHistory): void;
}
