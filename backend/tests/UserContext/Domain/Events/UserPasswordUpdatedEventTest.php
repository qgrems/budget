<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserPasswordUpdatedDomainEvent;
use PHPUnit\Framework\TestCase;

class UserPasswordUpdatedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserPasswordUpdatedDomainEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'oldpassword123', 'newpassword123');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('oldpassword123', $array['oldPassword']);
        $this->assertEquals('newpassword123', $array['newPassword']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'oldPassword' => 'oldpassword123',
            'newPassword' => 'newpassword123',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserPasswordUpdatedDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['oldPassword'], $event->oldPassword);
        $this->assertEquals($data['newPassword'], $event->newPassword);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
