<?php

namespace App\UserContext\Domain\Events;

use App\Libraries\Anonymii\Attributes\PersonalData;
use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;

final class UserFirstnameChangedDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $firstname;
    public string $userId;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $firstname,
        string $userId,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID)
    {
        $this->aggregateId = $aggregateId;
        $this->firstname = $firstname;
        $this->userId = $userId;
        $this->requestId = $requestId;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'requestId' => $this->requestId,
            'userId' => $this->userId,
            'firstname' => $this->firstname,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['firstname'], $data['userId'], $data['requestId']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
