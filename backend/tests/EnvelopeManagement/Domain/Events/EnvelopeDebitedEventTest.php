<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Domain\Events;

use App\EnvelopeManagement\Domain\Events\EnvelopeDebitedEvent;
use PHPUnit\Framework\TestCase;

class EnvelopeDebitedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new EnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '300.00');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('300.00', $array['debitMoney']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'debitMoney' => '300.00',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = EnvelopeDebitedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertEquals($data['debitMoney'], $event->getDebitMoney());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
