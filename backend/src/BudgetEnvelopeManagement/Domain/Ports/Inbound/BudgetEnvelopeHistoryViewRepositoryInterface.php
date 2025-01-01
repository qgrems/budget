<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Domain\Ports\Inbound;

use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeHistoryViewInterface;

interface BudgetEnvelopeHistoryViewRepositoryInterface
{
    public function save(BudgetEnvelopeHistoryViewInterface $budgetEnvelopeHistory): void;
}
