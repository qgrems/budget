<?php

namespace App\UserContext\Domain\Events;

use App\Libraries\FluxCapacitor\Anonymizer\Attributes\PersonalData;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;
use App\SharedContext\Domain\ValueObjects\UtcClock;

final class UserPasswordResetDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $password;
    public string $userId;
    public string $requestId;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $password,
        string $userId,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID
    ) {
        $this->aggregateId = $aggregateId;
        $this->password = $password;
        $this->userId = $userId;
        $this->requestId = $requestId;
        $this->occurredOn = UtcClock::immutableNow();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'requestId' => $this->requestId,
            'userId' => $this->userId,
            'password' => $this->password,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['password'], $data['userId'], $data['requestId']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
