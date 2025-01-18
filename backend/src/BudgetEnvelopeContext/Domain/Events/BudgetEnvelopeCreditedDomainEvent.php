<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;

final class BudgetEnvelopeCreditedDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $userId;
    public string $creditMoney;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $userId, string $creditMoney)
    {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->creditMoney = $creditMoney;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'creditMoney' => $this->creditMoney,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['userId'], $data['creditMoney']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
