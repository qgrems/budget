<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\ReadModels\Views;

use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeHistoryView;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeHistoryViewTest extends TestCase
{
    public function testFromRepository(): void
    {
        $data = [
            'aggregate_id' => '28b708ef-3192-421f-a94f-706d40bd0479',
            'created_at' => '2024-12-31 00:19:44',
            'monetary_amount' => '400.00',
            'transaction_type' => 'credit',
            'user_uuid' => '12ebaf73-7722-4013-b31f-9450a4105492',
        ];

        $historyView = BudgetEnvelopeHistoryView::fromRepository($data);

        $this->assertEquals($data['aggregate_id'], $historyView->getAggregateId());
        $this->assertEquals(new \DateTimeImmutable($data['created_at']), $historyView->getCreatedAt());
        $this->assertEquals($data['monetary_amount'], $historyView->getMonetaryAmount());
        $this->assertEquals($data['transaction_type'], $historyView->getTransactionType());
        $this->assertEquals($data['user_uuid'], $historyView->getUserUuid());
    }

    public function testSettersAndGetters(): void
    {
        $historyView = new BudgetEnvelopeHistoryView();

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
        $createdAt = new \DateTimeImmutable('2024-12-31 00:19:44');
        $historyView = new BudgetEnvelopeHistoryView()
            ->setMonetaryAmount('400.00')
            ->setTransactionType('credit')
            ->setCreatedAt($createdAt);

        $expected = [
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'monetary_amount' => '400.00',
            'transaction_type' => 'credit',
        ];

        $this->assertEquals($expected, $historyView->jsonSerialize());
    }
}
