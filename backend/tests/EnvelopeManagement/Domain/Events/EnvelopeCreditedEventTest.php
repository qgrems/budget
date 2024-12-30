<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Domain\Events;

use App\EnvelopeManagement\Domain\Events\EnvelopeCreditedEvent;
use PHPUnit\Framework\TestCase;

class EnvelopeCreditedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new EnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('500.00', $array['creditMoney']);
        $this->assertEquals($event->occurredOn()->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'creditMoney' => '500.00',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = EnvelopeCreditedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->getAggregateId());
        $this->assertEquals($data['creditMoney'], $event->getCreditMoney());
        $this->assertEquals($data['occurredOn'], $event->occurredOn()->format(\DateTimeInterface::ATOM));
    }
}
