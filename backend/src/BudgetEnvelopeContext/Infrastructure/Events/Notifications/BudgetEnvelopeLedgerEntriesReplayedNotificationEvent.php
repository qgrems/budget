<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;

final readonly class BudgetEnvelopeLedgerEntriesReplayedNotificationEvent
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
        $this->type = 'BudgetEnvelopeLedgerEntriesReplayed';
    }

    public static function fromDomainEvent(BudgetEnvelopeReplayedDomainEvent $budgetEnvelopeReplayedDomainEvent): self
    {
        return new self(
            $budgetEnvelopeReplayedDomainEvent->aggregateId,
            $budgetEnvelopeReplayedDomainEvent->userId,
            $budgetEnvelopeReplayedDomainEvent->requestId,
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
