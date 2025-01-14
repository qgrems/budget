<?php

namespace App\UserContext\Domain\Events;

use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;

final class UserPasswordResetRequestedDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    public string $passwordResetToken;
    public \DateTimeImmutable $passwordResetTokenExpiry;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $passwordResetToken,
        \DateTimeImmutable $passwordResetTokenExpiry,
    ) {
        $this->aggregateId = $aggregateId;
        $this->passwordResetToken = $passwordResetToken;
        $this->passwordResetTokenExpiry = $passwordResetTokenExpiry;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
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
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
