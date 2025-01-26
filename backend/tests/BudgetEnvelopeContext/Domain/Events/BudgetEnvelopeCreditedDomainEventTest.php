<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Domain\Events;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeCreditedDomainEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
            'test',
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('1ced5c7e-fd3a-4a36-808e-75ddc478f67b', $array['userId']);
        $this->assertEquals('500.00', $array['creditMoney']);
        $this->assertEquals('test', $array['description']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'creditMoney' => '500.00',
            'description' => 'test',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = BudgetEnvelopeCreditedDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['creditMoney'], $event->creditMoney);
        $this->assertEquals($data['description'], $event->description);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
