<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

final class BudgetEnvelopeAddedDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $userId;
    public string $name;
    public string $targetedAmount;
    public string $currency;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $userId,
        string $name,
        string $targetedAmount,
        string $currency,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ){
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->name = $name;
        $this->targetedAmount = $targetedAmount;
        $this->currency = $currency;
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
            'targetedAmount' => $this->targetedAmount,
            'currency' => $this->currency,
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
            $data['targetedAmount'],
            $data['currency'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
