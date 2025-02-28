<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;

final readonly class BudgetPlanIncomeAddedNotificationEvent
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
        $this->type = 'BudgetPlanIncomeAdded';
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

    public static function fromBudgetPlanIncomeAddedDomainEvent(
        BudgetPlanIncomeAddedDomainEvent $budgetPlanIncomeAddedDomainEvent,
    ): self {
        return new self(
            $budgetPlanIncomeAddedDomainEvent->aggregateId,
            $budgetPlanIncomeAddedDomainEvent->userId,
            $budgetPlanIncomeAddedDomainEvent->requestId,
        );
    }

    public static function fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
    ): self {
        return new self(
            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->aggregateId,
            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->userId,
            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->requestId,
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
