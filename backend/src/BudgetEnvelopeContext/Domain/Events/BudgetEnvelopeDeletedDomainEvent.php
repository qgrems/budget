<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;

final class BudgetEnvelopeDeletedDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $userId;
    public bool $isDeleted;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $userId, bool $isDeleted)
    {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->isDeleted = $isDeleted;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'isDeleted' => $this->isDeleted,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['userId'], $data['isDeleted']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
