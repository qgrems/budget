<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;

final readonly class BudgetPlanSavingAddedNotificationEvent
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
        $this->type = 'BudgetPlanSavingAdded';
    }

    public static function fromBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent
    ): self {
        return new self(
            $budgetPlanGeneratedDomainEvent->aggregateId,
            $budgetPlanGeneratedDomainEvent->userId,
            $budgetPlanGeneratedDomainEvent->requestId,
        );
    }

    public static function fromBudgetPlanSavingAddedDomainEvent(
        BudgetPlanSavingAddedDomainEvent $budgetPlanSavingAddedDomainEvent,
    ): self {
        return new self(
            $budgetPlanSavingAddedDomainEvent->aggregateId,
            $budgetPlanSavingAddedDomainEvent->userId,
            $budgetPlanSavingAddedDomainEvent->requestId,
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
