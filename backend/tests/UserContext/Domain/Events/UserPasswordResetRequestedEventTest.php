<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use PHPUnit\Framework\TestCase;

class UserPasswordResetRequestedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $passwordResetTokenExpiry = new \DateTimeImmutable('+1 hour');
        $event = new UserPasswordResetRequestedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'reset-token-123',
            $passwordResetTokenExpiry,
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('reset-token-123', $array['passwordResetToken']);
        $this->assertEquals($passwordResetTokenExpiry->format(\DateTimeInterface::ATOM), $array['passwordResetTokenExpiry']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'requestId' => '8f636cef-6a4d-40f1-a9cf-4e64f67ce7c0',
            'passwordResetToken' => 'reset-token-123',
            'passwordResetTokenExpiry' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserPasswordResetRequestedDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['requestId'], $event->requestId);
        $this->assertEquals($data['passwordResetToken'], $event->passwordResetToken);
        $this->assertEquals($data['passwordResetTokenExpiry'], $event->passwordResetTokenExpiry->format(\DateTimeInterface::ATOM));
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
