<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;

final readonly class BudgetPlanRemovedNotificationEvent
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
        $this->type = 'BudgetPlanRemoved';
    }

    public static function fromDomainEvent(BudgetPlanRemovedDomainEvent $budgetPlanRemovedDomainEvent): self
    {
        return new self(
            $budgetPlanRemovedDomainEvent->aggregateId,
            $budgetPlanRemovedDomainEvent->userId,
            $budgetPlanRemovedDomainEvent->requestId,
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
