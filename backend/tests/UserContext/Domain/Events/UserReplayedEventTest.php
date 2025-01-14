<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use PHPUnit\Framework\TestCase;

class UserReplayedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserReplayedDomainEvent(
            'aggregateId',
            'firstname',
            'lastname',
            'email',
            'password',
            true,
            '2021-09-01T00:00:00+00:00',
            '2021-09-01T00:00:00+00:00',
        );

        $this->assertEquals(
            [
                'aggregateId' => 'aggregateId',
                'email' => 'email',
                'password' => 'password',
                'firstname' => 'firstname',
                'lastname' => 'lastname',
                'isConsentGiven' => true,
                'consentDate' => '2021-09-01T00:00:00+00:00',
                'updatedAt' => '2021-09-01T00:00:00+00:00',
                'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ],
            $event->toArray()
        );
    }

    public function testFromArray(): void
    {
        $event = UserReplayedDomainEvent::fromArray([
            'aggregateId' => 'aggregateId',
            'email' => 'email',
            'password' => 'password',
            'firstname' => 'firstname',
            'lastname' => 'lastname',
            'isConsentGiven' => true,
            'consentDate' => '2021-09-01T00:00:00+00:00',
            'updatedAt' => '2021-09-01T00:00:00+00:00',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ]);

        $this->assertEquals('aggregateId', $event->aggregateId);
        $this->assertEquals('firstname', $event->firstname);
        $this->assertEquals('lastname', $event->lastname);
        $this->assertEquals('email', $event->email);
        $this->assertEquals('password', $event->password);
        $this->assertTrue($event->isConsentGiven);
        $this->assertEquals('2021-09-01T00:00:00+00:00', $event->consentDate->format(\DateTimeInterface::ATOM));
        $this->assertEquals('2021-09-01T00:00:00+00:00', $event->updatedAt->format(\DateTimeInterface::ATOM));
    }
}
