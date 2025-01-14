<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Domain\Events;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeRewoundDomainEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeRewoundDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '500.00',
            '2024-12-07T22:03:35+00:00',
            false
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('1ced5c7e-fd3a-4a36-808e-75ddc478f67b', $array['userId']);
        $this->assertEquals('Test', $array['name']);
        $this->assertEquals('1000.00', $array['targetedAmount']);
        $this->assertEquals('500.00', $array['currentAmount']);
        $this->assertEquals('2024-12-07T22:03:35+00:00', $array['updatedAt']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
        $this->assertFalse($array['isDeleted']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'name' => 'Test',
            'targetedAmount' => '1000.00',
            'currentAmount' => '500.00',
            'updatedAt' => '2024-12-07T22:03:35+00:00',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'isDeleted' => false,
        ];

        $event = BudgetEnvelopeRewoundDomainEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['userId'], $event->userId);
        $this->assertEquals($data['name'], $event->name);
        $this->assertEquals($data['targetedAmount'], $event->targetedAmount);
        $this->assertEquals($data['currentAmount'], $event->currentAmount);
        $this->assertEquals($data['updatedAt'], $event->updatedAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
        $this->assertFalse($event->isDeleted);
    }
}
