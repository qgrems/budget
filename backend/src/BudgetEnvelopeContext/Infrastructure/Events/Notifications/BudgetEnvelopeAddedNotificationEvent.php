<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;

final readonly class BudgetEnvelopeAddedNotificationEvent
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
        $this->type = 'BudgetEnvelopeAdded';
    }

    public static function fromDomainEvent(BudgetEnvelopeAddedDomainEvent $budgetEnvelopeAddedDomainEvent): self
    {
        return new self(
            $budgetEnvelopeAddedDomainEvent->aggregateId,
            $budgetEnvelopeAddedDomainEvent->userId,
            $budgetEnvelopeAddedDomainEvent->requestId,
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
