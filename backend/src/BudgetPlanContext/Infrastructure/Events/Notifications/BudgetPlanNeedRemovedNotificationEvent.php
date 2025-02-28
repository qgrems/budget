<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent;

final readonly class BudgetPlanNeedRemovedNotificationEvent
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
        $this->type = 'BudgetPlanNeedRemoved';
    }

    public static function fromDomainEvent(BudgetPlanNeedRemovedDomainEvent $budgetPlanNeedRemovedDomainEvent): self
    {
        return new self(
            $budgetPlanNeedRemovedDomainEvent->aggregateId,
            $budgetPlanNeedRemovedDomainEvent->userId,
            $budgetPlanNeedRemovedDomainEvent->requestId,
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
