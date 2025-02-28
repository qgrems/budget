<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;

final readonly class BudgetPlanIncomeRemovedNotificationEvent
{
    public string $aggregateId;
    public string $userId;
    public string $requestId;
    public string $type;

    private function __construct(
        string $aggregateId,
        string $userId,
        string $requestId,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->requestId = $requestId;
        $this->type = 'BudgetPlanIncomeRemoved';
    }

    public static function fromDomainEvent(BudgetPlanIncomeRemovedDomainEvent $budgetPlanIncomeRemovedDomainEvent): self
    {
        return new self(
            $budgetPlanIncomeRemovedDomainEvent->aggregateId,
            $budgetPlanIncomeRemovedDomainEvent->userId,
            $budgetPlanIncomeRemovedDomainEvent->requestId,
        );
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'requestId' => $this->requestId,
            'type' => $this->type,
        ];
    }
}
