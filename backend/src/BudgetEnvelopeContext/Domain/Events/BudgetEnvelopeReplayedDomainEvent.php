<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;

final class BudgetEnvelopeReplayedDomainEvent implements DomainEventInterface
{
    public string $aggregateId;
    public string $userId;
    public string $name;
    public string $targetedAmount;
    public string $currentAmount;
    public bool $isDeleted;
    public \DateTime $updatedAt;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $userId,
        string $name,
        string $targetedAmount,
        string $currentAmount,
        string $updatedAt,
        bool $isDeleted,
    ) {
        $this->aggregateId = $aggregateId;
        $this->userId = $userId;
        $this->name = $name;
        $this->targetedAmount = $targetedAmount;
        $this->currentAmount = $currentAmount;
        $this->updatedAt = new \DateTime($updatedAt);
        $this->isDeleted = $isDeleted;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'userId' => $this->userId,
            'name' => $this->name,
            'targetedAmount' => $this->targetedAmount,
            'currentAmount' => $this->currentAmount,
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
            'isDeleted' => $this->isDeleted,
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
            $data['currentAmount'],
            $data['updatedAt'],
            $data['isDeleted'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
