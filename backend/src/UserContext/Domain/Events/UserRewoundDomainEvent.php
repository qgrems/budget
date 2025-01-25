<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Events;

use App\UserContext\Domain\Attributes\PersonalData;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;

final class UserRewoundDomainEvent implements UserDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $firstname;
    #[PersonalData]
    public string $lastname;
    #[PersonalData]
    public string $email;
    #[PersonalData]
    public string $password;
    #[PersonalData]
    public string $languagePreference;
    public bool $isConsentGiven;
    public \DateTime $updatedAt;
    public \DateTimeImmutable $consentDate;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $firstname,
        string $lastname,
        string $languagePreference,
        string $email,
        string $password,
        bool $isConsentGiven,
        string $consentDate,
        string $updatedAt,
    ) {
        $this->aggregateId = $aggregateId;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->languagePreference = $languagePreference;
        $this->email = $email;
        $this->password = $password;
        $this->isConsentGiven = $isConsentGiven;
        $this->updatedAt = new \DateTime($updatedAt);
        $this->consentDate = new \DateTimeImmutable($consentDate);
        $this->occurredOn = new \DateTimeImmutable();
    }

    #[\Override]
    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'email' => $this->email,
            'password' => $this->password,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'languagePreference' => $this->languagePreference,
            'isConsentGiven' => $this->isConsentGiven,
            'consentDate' => $this->consentDate->format(\DateTimeInterface::ATOM),
            'updatedAt' => $this->updatedAt->format(\DateTimeInterface::ATOM),
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['firstname'],
            $data['lastname'],
            $data['languagePreference'],
            $data['email'],
            $data['password'],
            $data['isConsentGiven'],
            $data['consentDate'],
            $data['updatedAt'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
