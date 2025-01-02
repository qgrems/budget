<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Domain\Events;

use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeDeletedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b', true);
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertTrue($array['isDeleted']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'isDeleted' => true,
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = BudgetEnvelopeDeletedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertTrue($event->isDeleted());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
