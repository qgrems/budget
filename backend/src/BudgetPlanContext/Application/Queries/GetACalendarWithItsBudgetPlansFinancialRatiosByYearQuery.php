<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Queries;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\SharedContext\Domain\Ports\Inbound\QueryInterface;

final readonly class GetACalendarWithItsBudgetPlansFinancialRatiosByYearQuery implements QueryInterface
{
    private string $budgetPlanUserId;
    private string $date;

    public function __construct(
        BudgetPlanUserId $budgetPlanUserId,
        \DateTimeImmutable $date,
    ) {
        $this->budgetPlanUserId = (string) $budgetPlanUserId;
        $this->date = $date->format('Y');
    }

    public function getBudgetPlanUserId(): BudgetPlanUserId
    {
        return BudgetPlanUserId::fromString($this->budgetPlanUserId);
    }

    public function getDate(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromFormat('Y', $this->date);
    }
}
