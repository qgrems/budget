<?php

namespace App\UserContext\Domain\Events;

use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\DomainEventInterface;

final class UserPasswordResetRequestedDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    public string $passwordResetToken;
    public string $userId;
    public string $requestId;
    public \DateTimeImmutable $passwordResetTokenExpiry;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $passwordResetToken,
        \DateTimeImmutable $passwordResetTokenExpiry,
        string $userId,
        string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,

    ) {
        $this->aggregateId = $aggregateId;
        $this->passwordResetToken = $passwordResetToken;
        $this->passwordResetTokenExpiry = $passwordResetTokenExpiry;
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
            'passwordResetToken' => $this->passwordResetToken,
            'passwordResetTokenExpiry' => $this->passwordResetTokenExpiry->format(\DateTimeInterface::ATOM),
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['passwordResetToken'],
            new \DateTimeImmutable(
                $data['passwordResetTokenExpiry']
            ),
            $data['userId'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
