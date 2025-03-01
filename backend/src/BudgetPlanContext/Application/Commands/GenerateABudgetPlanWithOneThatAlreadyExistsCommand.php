<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Commands;

use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanId;
use App\BudgetPlanContext\Domain\ValueObjects\BudgetPlanUserId;
use App\SharedContext\Domain\Ports\Inbound\CommandInterface;

final readonly class GenerateABudgetPlanWithOneThatAlreadyExistsCommand implements CommandInterface
{
    private string $budgetPlanId;
    private string $budgetPlanIdThatAlreadyExists;
    private \DateTimeImmutable $date;
    private string $userId;

    public function __construct(
        BudgetPlanId $budgetPlanId,
        BudgetPlanId $budgetPlanIdThatAlreadyExists,
        \DateTimeImmutable $date,
        BudgetPlanUserId $userId,
    ) {
        $this->budgetPlanId = (string) $budgetPlanId;
        $this->budgetPlanIdThatAlreadyExists = (string) $budgetPlanIdThatAlreadyExists;
        $this->date = $date;
        $this->userId = (string) $userId;
    }

    public function getBudgetPlanId(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanId);
    }

    public function getBudgetPlanIdThatAlreadyExists(): BudgetPlanId
    {
        return BudgetPlanId::fromString($this->budgetPlanIdThatAlreadyExists);
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getUserId(): BudgetPlanUserId
    {
        return BudgetPlanUserId::fromString($this->userId);
    }
}
