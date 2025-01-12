<?php

namespace App\UserContext\Domain\Events;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final class UserPasswordUpdatedEvent implements EventInterface
{
    public string $aggregateId;
    public string $oldPassword;
    public string $newPassword;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $oldPassword, string $newPassword)
    {
        $this->aggregateId = $aggregateId;
        $this->oldPassword = $oldPassword;
        $this->newPassword = $newPassword;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'oldPassword' => $this->oldPassword,
            'newPassword' => $this->newPassword,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['oldPassword'], $data['newPassword']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
