<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Ports\Inbound;

interface BudgetEnvelopeHistoryViewRepositoryInterface
{
    public function save(BudgetEnvelopeHistoryViewInterface $budgetEnvelopeHistoryView): void;
}
