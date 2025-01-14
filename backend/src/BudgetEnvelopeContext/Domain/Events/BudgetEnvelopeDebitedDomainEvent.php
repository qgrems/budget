<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;

final class BudgetEnvelopeDebitedDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $userId;
    public string $debitMoney;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $userId, string $debitMoney)
    {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->debitMoney = $debitMoney;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'debitMoney' => $this->debitMoney,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['userId'], $data['debitMoney']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
