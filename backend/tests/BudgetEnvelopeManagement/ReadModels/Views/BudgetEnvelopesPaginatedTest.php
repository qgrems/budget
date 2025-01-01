<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\ReadModels\Views;

use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopesPaginated;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopesPaginatedTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $envelope1 = new BudgetEnvelopeView();
        $envelope1->setId(1)->setName('Envelope 1');

        $envelope2 = new BudgetEnvelopeView();
        $envelope2->setId(2)->setName('Envelope 2');

        $envelopes = [$envelope1, $envelope2];
        $totalItems = 2;
        $envelopesPaginated = new BudgetEnvelopesPaginated($envelopes, $totalItems);

        $expected = [
            'envelopes' => $envelopes,
            'totalItems' => $totalItems,
        ];

        $this->assertEquals($expected, $envelopesPaginated->jsonSerialize());
    }

    public function testGetEnvelopes(): void
    {
        $envelopes = [$this->createMock(\stdClass::class)];
        $envelopesPaginated = new BudgetEnvelopesPaginated($envelopes, 1);
        $this->assertEquals($envelopes, $envelopesPaginated->getEnvelopes());
    }

    public function testGetTotalItems(): void
    {
        $envelopes = [$this->createMock(\stdClass::class)];
        $envelopesPaginated = new BudgetEnvelopesPaginated($envelopes, 1);
        $this->assertEquals(1, $envelopesPaginated->getTotalItems());
    }
}
