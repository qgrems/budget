<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;

final readonly class BudgetEnvelopeRenamedNotificationEvent
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
        $this->type = 'BudgetEnvelopeRenamed';
    }

    public static function fromDomainEvent(BudgetEnvelopeRenamedDomainEvent $budgetEnvelopeRenamedDomainEvent): self
    {
        return new self(
            $budgetEnvelopeRenamedDomainEvent->aggregateId,
            $budgetEnvelopeRenamedDomainEvent->userId,
            $budgetEnvelopeRenamedDomainEvent->requestId,
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
