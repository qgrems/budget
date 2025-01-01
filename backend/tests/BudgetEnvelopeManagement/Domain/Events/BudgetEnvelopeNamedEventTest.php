<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Domain\Events;

use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeNamedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeRenamedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Test Name');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('Test Name', $array['name']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'name' => 'Test Name',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = BudgetEnvelopeRenamedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertEquals($data['name'], $event->getName());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
