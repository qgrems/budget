<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;

final readonly class BudgetPlanWantRemovedNotificationEvent
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
        $this->type = 'BudgetPlanWantRemoved';
    }

    public static function fromDomainEvent(BudgetPlanWantRemovedDomainEvent $budgetPlanWantRemovedDomainEvent): self
    {
        return new self(
            $budgetPlanWantRemovedDomainEvent->aggregateId,
            $budgetPlanWantRemovedDomainEvent->userId,
            $budgetPlanWantRemovedDomainEvent->requestId,
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
