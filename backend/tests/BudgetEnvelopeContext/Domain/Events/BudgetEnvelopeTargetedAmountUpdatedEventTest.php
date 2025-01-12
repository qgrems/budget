<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Domain\Events;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeTargetedAmountUpdatedEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new BudgetEnvelopeTargetedAmountUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('500.00', $array['targetedAmount']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'targetedAmount' => '500.00',
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = BudgetEnvelopeTargetedAmountUpdatedEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['targetedAmount'], $event->targetedAmount);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
