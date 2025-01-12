<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final class BudgetEnvelopeTargetedAmountUpdatedEvent implements EventInterface
{
    public string $aggregateId;
    public string $targetedAmount;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $targetedAmount)
    {
        $this->aggregateId = $aggregateId;
        $this->targetedAmount = $targetedAmount;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'targetedAmount' => $this->targetedAmount,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['targetedAmount']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
