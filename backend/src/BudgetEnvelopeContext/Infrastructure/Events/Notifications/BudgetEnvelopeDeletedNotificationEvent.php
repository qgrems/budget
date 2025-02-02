<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;

final readonly class BudgetEnvelopeDeletedNotificationEvent
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
        $this->type = 'BudgetEnvelopeDeleted';
    }

    public static function fromDomainEvent(BudgetEnvelopeDeletedDomainEvent $budgetEnvelopeDeletedDomainEvent): self
    {
        return new self(
            $budgetEnvelopeDeletedDomainEvent->aggregateId,
            $budgetEnvelopeDeletedDomainEvent->userId,
            $budgetEnvelopeDeletedDomainEvent->requestId,
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
