<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCurrencyChangedDomainEvent;

final readonly class BudgetEnvelopeCurrencyChangedNotificationEvent
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
        $this->type = 'BudgetEnvelopeCurrencyChanged';
    }

    public static function fromDomainEvent(
        BudgetEnvelopeCurrencyChangedDomainEvent $budgetEnvelopeCurrencyChangedDomainEvent,
    ): self {
        return new self(
            $budgetEnvelopeCurrencyChangedDomainEvent->aggregateId,
            $budgetEnvelopeCurrencyChangedDomainEvent->userId,
            $budgetEnvelopeCurrencyChangedDomainEvent->requestId,
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
