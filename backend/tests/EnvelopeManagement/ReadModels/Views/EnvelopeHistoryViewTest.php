<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\ReadModels\Views;

use App\EnvelopeManagement\ReadModels\Views\EnvelopeHistoryView;
use PHPUnit\Framework\TestCase;

class EnvelopeHistoryViewTest extends TestCase
{
    public function testCreate(): void
    {
        $aggregateId = '28b708ef-3192-421f-a94f-706d40bd0479';
        $createdAt = new \DateTimeImmutable('2024-12-31 00:19:44');
        $monetaryAmount = '400.00';
        $transactionType = 'credit';
        $userUuid = '12ebaf73-7722-4013-b31f-9450a4105492';

        $historyView = EnvelopeHistoryView::create($aggregateId, $createdAt, $monetaryAmount, $transactionType, $userUuid);

        $this->assertInstanceOf(EnvelopeHistoryView::class, $historyView);
        $this->assertEquals($aggregateId, $historyView->getAggregateId());
        $this->assertEquals($createdAt, $historyView->getCreatedAt());
        $this->assertEquals($monetaryAmount, $historyView->getMonetaryAmount());
        $this->assertEquals($transactionType, $historyView->getTransactionType());
        $this->assertEquals($userUuid, $historyView->getUserUuid());
    }

    public function testSettersAndGetters(): void
    {
        $historyView = new EnvelopeHistoryView();

        $historyView->setId(1);
        $this->assertEquals(1, $historyView->getId());

        $aggregateId = '28b708ef-3192-421f-a94f-706d40bd0479';
        $historyView->setAggregateId($aggregateId);
        $this->assertEquals($aggregateId, $historyView->getAggregateId());

        $createdAt = new \DateTimeImmutable('2024-12-31 00:19:44');
        $historyView->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $historyView->getCreatedAt());

        $monetaryAmount = '400.00';
        $historyView->setMonetaryAmount($monetaryAmount);
        $this->assertEquals($monetaryAmount, $historyView->getMonetaryAmount());

        $transactionType = 'credit';
        $historyView->setTransactionType($transactionType);
        $this->assertEquals($transactionType, $historyView->getTransactionType());

        $userUuid = '12ebaf73-7722-4013-b31f-9450a4105492';
        $historyView->setUserUuid($userUuid);
        $this->assertEquals($userUuid, $historyView->getUserUuid());
    }

    public function testJsonSerialize(): void
    {
        $aggregateId = '28b708ef-3192-421f-a94f-706d40bd0479';
        $createdAt = new \DateTimeImmutable('2024-12-31 00:19:44');
        $monetaryAmount = '400.00';
        $transactionType = 'credit';
        $userUuid = '12ebaf73-7722-4013-b31f-9450a4105492';

        $historyView = EnvelopeHistoryView::create($aggregateId, $createdAt, $monetaryAmount, $transactionType, $userUuid);

        $expectedJson = [
            'aggregate_id' => $aggregateId,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'monetary_amount' => $monetaryAmount,
            'transaction_type' => $transactionType,
            'user_uuid' => $userUuid,
        ];

        $this->assertEquals($expectedJson, $historyView->jsonSerialize());
    }
}
