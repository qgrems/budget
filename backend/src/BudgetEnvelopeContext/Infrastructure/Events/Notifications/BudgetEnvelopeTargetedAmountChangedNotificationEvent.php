<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;

final readonly class BudgetEnvelopeTargetedAmountChangedNotificationEvent
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
        $this->type = 'BudgetEnvelopeTargetedAmountChanged';
    }

    public static function fromDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $budgetEnvelopeTargetedAmountChangedDomainEvent,
    ): self {
        return new self(
            $budgetEnvelopeTargetedAmountChangedDomainEvent->aggregateId,
            $budgetEnvelopeTargetedAmountChangedDomainEvent->userId,
            $budgetEnvelopeTargetedAmountChangedDomainEvent->requestId,
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
