<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\ReadModels\Views;

use App\EnvelopeManagement\ReadModels\Views\EnvelopesPaginated;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeView;
use PHPUnit\Framework\TestCase;

class EnvelopesPaginatedTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $envelope1 = new EnvelopeView();
        $envelope1->setId(1)->setName('Envelope 1');

        $envelope2 = new EnvelopeView();
        $envelope2->setId(2)->setName('Envelope 2');

        $envelopes = [$envelope1, $envelope2];
        $totalItems = 2;
        $envelopesPaginated = new EnvelopesPaginated($envelopes, $totalItems);

        $expected = [
            'envelopes' => $envelopes,
            'totalItems' => $totalItems,
        ];

        $this->assertEquals($expected, $envelopesPaginated->jsonSerialize());
    }

    public function testGetEnvelopes(): void
    {
        $envelopes = [$this->createMock(\stdClass::class)];
        $envelopesPaginated = new EnvelopesPaginated($envelopes, 1);
        $this->assertEquals($envelopes, $envelopesPaginated->getEnvelopes());
    }

    public function testGetTotalItems(): void
    {
        $envelopes = [$this->createMock(\stdClass::class)];
        $envelopesPaginated = new EnvelopesPaginated($envelopes, 1);
        $this->assertEquals(1, $envelopesPaginated->getTotalItems());
    }
}
