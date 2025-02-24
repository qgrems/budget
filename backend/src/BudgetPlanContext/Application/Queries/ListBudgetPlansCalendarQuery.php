<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Queries;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\SharedContext\Domain\Ports\Inbound\QueryInterface;

final readonly class ListBudgetPlansCalendarQuery implements QueryInterface
{
    private string $budgetPlanUserId;

    public function __construct(
        BudgetPlanUserId $budgetPlanUserId,
    ) {
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
    }

    public function getBudgetPlanUserId(): BudgetPlanUserId
    {
        return BudgetPlanUserId::fromString($this->budgetPlanUserId);
    }
}
