<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;

final readonly class BudgetPlanGeneratedWithOneThatAlreadyExistsNotificationEvent
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
        $this->type = 'BudgetPlanGeneratedWithOneThatAlreadyExists';
    }

    public static function fromDomainEvent(
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
