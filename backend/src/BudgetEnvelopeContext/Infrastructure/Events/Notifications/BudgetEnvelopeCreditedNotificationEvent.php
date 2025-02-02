<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Infrastructure\Events\Notifications;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;

final readonly class BudgetEnvelopeCreditedNotificationEvent
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
        $this->type = 'BudgetEnvelopeCredited';
    }

    public static function fromDomainEvent(BudgetEnvelopeCreditedDomainEvent $budgetEnvelopeCreditedDomainEvent): self
    {
        return new self(
            $budgetEnvelopeCreditedDomainEvent->aggregateId,
            $budgetEnvelopeCreditedDomainEvent->userId,
            $budgetEnvelopeCreditedDomainEvent->requestId,
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
