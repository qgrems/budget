<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;

final readonly class BudgetPlanSavingRemovedNotificationEvent
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
        $this->type = 'BudgetPlanSavingRemoved';
    }

    public static function fromDomainEvent(
        BudgetPlanSavingRemovedDomainEvent $budgetPlanSavingRemovedDomainEvent
    ): self {
        return new self(
            $budgetPlanSavingRemovedDomainEvent->aggregateId,
            $budgetPlanSavingRemovedDomainEvent->userId,
            $budgetPlanSavingRemovedDomainEvent->requestId,
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
