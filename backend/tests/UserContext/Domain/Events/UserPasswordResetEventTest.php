<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserPasswordResetEvent;
use PHPUnit\Framework\TestCase;

class UserPasswordResetEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserPasswordResetEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'newpassword123');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('newpassword123', $array['password']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'password' => 'newpassword123',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserPasswordResetEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['password'], $event->password);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
