<?php

namespace App\UserContext\Domain\Events;

use App\UserContext\Domain\Attributes\PersonalData;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;

final class UserLanguagePreferenceUpdatedDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $languagePreference;
    public \DateTimeImmutable $occurredOn;

    public function __construct(string $aggregateId, string $languagePreference)
    {
        $this->aggregateId = $aggregateId;
        $this->languagePreference = $languagePreference;
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'languagePreference' => $this->languagePreference,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self($data['aggregateId'], $data['languagePreference']);
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
