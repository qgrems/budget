<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\ReadModels\Views;

use App\EnvelopeManagement\ReadModels\Views\EnvelopeView;
use PHPUnit\Framework\TestCase;

class EnvelopeViewTest extends TestCase
{
    public function testJsonSerialize(): void
    {
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid('b7e685be-db83-4866-9f85-102fac30a50b')
            ->setCurrentBudget('500.00')
            ->setTargetBudget('1000.00')
            ->setName('Test Envelope');

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'currentBudget' => '500.00',
            'targetBudget' => '1000.00',
            'name' => 'Test Envelope',
        ];

        $this->assertEquals($expected, $envelopeView->jsonSerialize());
    }

    public function testGetId(): void
    {
        $envelopeView = new EnvelopeView();
        $reflection = new \ReflectionClass($envelopeView);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($envelopeView, 1);

        $this->assertEquals(1, $envelopeView->getId());
    }

    public function testSetId(): void
    {
        $envelopeView = new EnvelopeView();
        $envelopeView->setId(1);

        $this->assertEquals(1, $envelopeView->getId());
    }
}
