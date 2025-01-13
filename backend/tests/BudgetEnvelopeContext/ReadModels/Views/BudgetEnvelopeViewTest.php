<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeViewTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
            ),
        );

        $envelopeView->fromEvent(
            new BudgetEnvelopeCreditedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '500.00',
            ),
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentAmount' => '500',
            'targetedAmount' => '1000.00',
            'name' => 'Test Envelope',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }

    public function testApplyCreatedEvent(): void
    {
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
            ),
        );

        $envelopeView->fromEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
            ),
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentAmount' => '0.00',
            'targetedAmount' => '1000.00',
            'name' => 'Test Envelope',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }

    public function testFromEvents(): void
    {
        $envelopeView = BudgetEnvelopeView::fromEvents(
            (function () {
                yield [
                    'type' => BudgetEnvelopeCreatedEvent::class,
                    'payload' => json_encode([
                        'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                        'name' => 'Test Envelope',
                        'targetedAmount' => '1000.00',
                        'occurredOn' => '2023-01-01T00:00:00+00:00',
                    ]),
                ];
                yield [
                    'type' => BudgetEnvelopeCreditedEvent::class,
                    'payload' => json_encode([
                        'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'creditMoney' => '500.00',
                        'occurredOn' => '2023-01-01T00:00:00+00:00',
                    ]),
                ];
            })(),
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentAmount' => '500',
            'targetedAmount' => '1000.00',
            'name' => 'Test Envelope',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }
}
