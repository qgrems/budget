<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

final class BudgetEnvelopeNameReleasedDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $userId;
    public string $name;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $userId,
        string $name,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->name = $name;
        $this->requestId = $requestId;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'requestId' => $this->requestId,
            'name' => $this->name,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['userId'],
            $data['name'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
