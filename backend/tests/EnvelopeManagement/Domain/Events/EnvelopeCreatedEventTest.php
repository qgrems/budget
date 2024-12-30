<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Domain\Events;

use App\EnvelopeManagement\Domain\Events\EnvelopeCreatedEvent;
use PHPUnit\Framework\TestCase;

class EnvelopeCreatedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new EnvelopeCreatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '1ced5c7e-fd3a-4a36-808e-75ddc478f67b', 'Test', '1000.00');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('1ced5c7e-fd3a-4a36-808e-75ddc478f67b', $array['userId']);
        $this->assertEquals('Test', $array['name']);
        $this->assertEquals('1000.00', $array['targetBudget']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'name' => 'Test',
            'targetBudget' => '1000.00',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = EnvelopeCreatedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertEquals($data['userId'], $event->getUserId());
        $this->assertEquals($data['name'], $event->getName());
        $this->assertEquals($data['targetBudget'], $event->getTargetBudget());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
