<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;

final readonly class BudgetEnvelopeRewoundNotificationEvent
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
        $this->type = 'BudgetEnvelopeRewound';
    }

    public static function fromDomainEvent(BudgetEnvelopeRewoundDomainEvent $budgetEnvelopeRewoundDomainEvent): self
    {
        return new self(
            $budgetEnvelopeRewoundDomainEvent->aggregateId,
            $budgetEnvelopeRewoundDomainEvent->userId,
            $budgetEnvelopeRewoundDomainEvent->requestId,
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
