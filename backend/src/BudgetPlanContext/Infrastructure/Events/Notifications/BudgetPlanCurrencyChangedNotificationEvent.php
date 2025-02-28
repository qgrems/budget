<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Infrastructure\Events\Notifications;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent;

final readonly class BudgetPlanCurrencyChangedNotificationEvent
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
        $this->type = 'BudgetPlanCurrencyChanged';
    }

    public static function fromDomainEvent(BudgetPlanCurrencyChangedDomainEvent $budgetPlanCurrencyChangedDomainEvent): self
    {
        return new self(
            $budgetPlanCurrencyChangedDomainEvent->aggregateId,
            $budgetPlanCurrencyChangedDomainEvent->userId,
            $budgetPlanCurrencyChangedDomainEvent->requestId,
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
