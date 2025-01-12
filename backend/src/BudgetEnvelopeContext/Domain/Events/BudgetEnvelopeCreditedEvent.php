<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final class BudgetEnvelopeCreditedEvent implements EventInterface
{
    public string $aggregateId;
    public string $creditMoney;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $creditMoney)
    {
        $this->aggregateId = $aggregateId;
        $this->creditMoney = $creditMoney;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'creditMoney' => $this->creditMoney,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['creditMoney']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
