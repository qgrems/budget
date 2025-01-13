<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserPasswordResetRequestedEvent;
use PHPUnit\Framework\TestCase;

class UserPasswordResetRequestedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $passwordResetTokenExpiry = new \DateTimeImmutable('+1 hour');
        $event = new UserPasswordResetRequestedEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'reset-token-123',
            $passwordResetTokenExpiry
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
            'passwordResetToken' => 'reset-token-123',
            'passwordResetTokenExpiry' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserPasswordResetRequestedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['passwordResetToken'], $event->passwordResetToken);
        $this->assertEquals($data['passwordResetTokenExpiry'], $event->passwordResetTokenExpiry->format(\DateTimeInterface::ATOM));
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
