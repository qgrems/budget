<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Queries;

use App\BudgetEnvelopeContext\Domain\Ports\Inbound\QueryInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;

final readonly class GetABudgetEnvelopeWithItsHistoryQuery implements QueryInterface
{
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }

    public function getBudgetEnvelopeUserId(): BudgetEnvelopeUserId
    {
        return BudgetEnvelopeUserId::fromString($this->budgetEnvelopeUserId);
    }
}
