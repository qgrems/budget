<?php

namespace App\UserContext\Domain\Events;

use App\UserContext\Domain\Attributes\PersonalData;
use App\UserContext\Domain\Ports\Inbound\UserSignedUpDomainEventInterface;

final class UserSignedUpDomainEvent implements UserSignedUpDomainEventInterface
{
    public string $aggregateId;
    #[PersonalData]
    public string $email;
    #[PersonalData]
    public string $password;
    #[PersonalData]
    public string $firstname;
    #[PersonalData]
    public string $lastname;
    public bool $isConsentGiven;
    public array $roles;
    public \DateTimeImmutable $occurredOn;

    public function __construct(
        string $aggregateId,
        string $email,
        string $password,
        string $firstname,
        string $lastname,
        bool $isConsentGiven,
        array $roles,
    ) {
        $this->aggregateId = $aggregateId;
        $this->email = $email;
        $this->password = $password;
        $this->firstname = $firstname;
        $this->lastname = $lastname;
        $this->isConsentGiven = $isConsentGiven;
        $this->roles = $roles;
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
            'isConsentGiven' => $this->isConsentGiven,
            'roles' => $this->roles,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    #[\Override]
    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['email'],
            $data['password'],
            $data['firstname'],
            $data['lastname'],
            $data['isConsentGiven'],
            $data['roles'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);

        return $event;
    }
}
