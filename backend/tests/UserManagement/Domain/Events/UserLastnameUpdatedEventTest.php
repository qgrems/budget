<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Domain\Events;

use App\UserManagement\Domain\Events\UserLastnameUpdatedEvent;
use PHPUnit\Framework\TestCase;

class UserLastnameUpdatedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserLastnameUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Doe');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('Doe', $array['lastname']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'lastname' => 'Doe',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserLastnameUpdatedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertEquals($data['lastname'], $event->getLastname());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
