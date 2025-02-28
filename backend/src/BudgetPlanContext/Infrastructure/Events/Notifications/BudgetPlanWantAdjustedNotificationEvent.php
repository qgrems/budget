<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;

final readonly class BudgetPlanWantAdjustedNotificationEvent
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
        $this->type = 'BudgetPlanWantAdjusted';
    }

    public static function fromBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): self {
        return new self(
            $budgetPlanGeneratedDomainEvent->aggregateId,
            $budgetPlanGeneratedDomainEvent->userId,
            $budgetPlanGeneratedDomainEvent->requestId,
        );
    }

    public static function fromBudgetPlanWantAdjustedDomainEvent(
        BudgetPlanWantAdjustedDomainEvent $budgetPlanWantAdjustedDomainEvent,
    ): self {
        return new self(
            $budgetPlanWantAdjustedDomainEvent->aggregateId,
            $budgetPlanWantAdjustedDomainEvent->userId,
            $budgetPlanWantAdjustedDomainEvent->requestId,
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
