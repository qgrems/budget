<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Commands;

use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\CommandInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeTargetBudget;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;

final readonly class CreateABudgetEnvelopeCommand implements CommandInterface
{
    private string $budgetEnvelopeId;
    private string $budgetEnvelopeUserId;
    private string $budgetEnvelopeName;
    private string $budgetEnvelopeTargetBudget;

    public function __construct(
        BudgetEnvelopeId $budgetEnvelopeId,
        BudgetEnvelopeUserId $budgetEnvelopeUserId,
        BudgetEnvelopeName $budgetEnvelopeName,
        BudgetEnvelopeTargetBudget $budgetEnvelopeTargetBudget,
    ) {
        $this->budgetEnvelopeId = (string) $budgetEnvelopeId;
        $this->budgetEnvelopeUserId = (string) $budgetEnvelopeUserId;
        $this->budgetEnvelopeName = (string) $budgetEnvelopeName;
        $this->budgetEnvelopeTargetBudget = (string) $budgetEnvelopeTargetBudget;
    }

    public function getBudgetEnvelopeUserId(): BudgetEnvelopeUserId
    {
        return BudgetEnvelopeUserId::fromString($this->budgetEnvelopeUserId);
    }

    public function getBudgetEnvelopeId(): BudgetEnvelopeId
    {
        return BudgetEnvelopeId::fromString($this->budgetEnvelopeId);
    }

    public function getBudgetEnvelopeName(): BudgetEnvelopeName
    {
        return BudgetEnvelopeName::fromString($this->budgetEnvelopeName);
    }

    public function getBudgetEnvelopeTargetBudget(): BudgetEnvelopeTargetBudget
    {
        return BudgetEnvelopeTargetBudget::fromString($this->budgetEnvelopeTargetBudget);
    }
}
