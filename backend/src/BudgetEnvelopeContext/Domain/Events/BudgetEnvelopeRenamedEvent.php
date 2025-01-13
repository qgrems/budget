<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final class BudgetEnvelopeRenamedEvent implements EventInterface
{
    public string $aggregateId;
    public string $name;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $name)
    {
        $this->aggregateId = $aggregateId;
        $this->name = $name;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'name' => $this->name,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['name']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
