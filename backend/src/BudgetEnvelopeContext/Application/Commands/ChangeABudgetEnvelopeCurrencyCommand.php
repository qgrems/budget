<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Commands;

use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\CommandInterface;

final readonly class ChangeABudgetEnvelopeCurrencyCommand implements CommandInterface
{
    private string $budgetEnvelopeCurrency;
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;

    public function __construct(
        BudgetEnvelopeCurrency $budgetEnvelopeCurrency,
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
    ) {
        $this->budgetEnvelopeCurrency = (string) $budgetEnvelopeCurrency;
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
    }

    public function getBudgetEnvelopeCurrency(): BudgetEnvelopeCurrency
    {
        return BudgetEnvelopeCurrency::fromString($this->budgetEnvelopeCurrency);
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
