<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final class BudgetEnvelopeDeletedEvent implements EventInterface
{
    public string $aggregateId;
    public bool $isDeleted;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, bool $isDeleted)
    {
        $this->aggregateId = $aggregateId;
        $this->isDeleted = $isDeleted;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'isDeleted' => $this->isDeleted,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['isDeleted']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
