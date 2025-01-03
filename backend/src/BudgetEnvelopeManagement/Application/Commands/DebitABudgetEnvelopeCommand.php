<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Commands;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\CommandInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;

final readonly class DebitABudgetEnvelopeCommand implements CommandInterface
{
    private string $budgetEnvelopeDebitMoney;
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeDebitMoney $budgetEnvelopeDebitMoney,
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeDebitMoney = (string) $budgetEnvelopeDebitMoney;
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeDebitMoney(): BudgetEnvelopeDebitMoney
    {
        return BudgetEnvelopeDebitMoney::fromString($this->budgetEnvelopeDebitMoney);
    }

    public function getBudgetEnvelopeUserId(): BudgetEnvelopeUserId
    {
        return BudgetEnvelopeUserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }
}
