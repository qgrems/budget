<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeViewTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
                'USD',
            ),
        );

        $envelopeView->fromEvent(
            new BudgetEnvelopeCreditedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                '500.00',
                'test',
            ),
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentAmount' => '500',
            'targetedAmount' => '1000.00',
            'name' => 'Test Envelope',
            'currency' => 'USD',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }

    public function testApplyAddedEvent(): void
    {
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
                'USD',
            ),
        );

        $envelopeView->fromEvent(
            new BudgetEnvelopeAddedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test Envelope',
                '1000.00',
                'USD',
            ),
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentAmount' => '0.00',
            'targetedAmount' => '1000.00',
            'name' => 'Test Envelope',
            'currency' => 'USD',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }

    public function testFromEvents(): void
    {
        $envelopeView = BudgetEnvelopeView::fromEvents(
            (function () {
                yield [
                    'type' => BudgetEnvelopeAddedDomainEvent::class,
                    'payload' => json_encode([
                        'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                        'name' => 'Test Envelope',
                        'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                        'targetedAmount' => '1000.00',
                        'currency' => 'USD',
                        'occurredOn' => '2023-01-01T00:00:00+00:00',
                    ]),
                ];
                yield [
                    'type' => BudgetEnvelopeCreditedDomainEvent::class,
                    'payload' => json_encode([
                        'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'userId' => '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                        'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                        'creditMoney' => '500.00',
                        'description' => 'test',
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
            'currency' => 'USD',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }
}
