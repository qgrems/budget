<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Views;

use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeHistoryView;
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

        $this->assertEquals($data['aggregate_id'], $historyView->budgetEnvelopeUuid);
        $this->assertEquals(new \DateTimeImmutable($data['created_at']), $historyView->createdAt);
        $this->assertEquals($data['monetary_amount'], $historyView->monetaryAmount);
        $this->assertEquals($data['transaction_type'], $historyView->transactionType);
        $this->assertEquals($data['user_uuid'], $historyView->userUuid);
    }

    public function testJsonSerialize(): void
    {
        $createdAt = new \DateTimeImmutable('2024-12-31 00:19:44');
        $historyView = BudgetEnvelopeHistoryView::fromRepository([
            'aggregate_id' => '28b708ef-3192-421f-a94f-706d40bd0479',
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'monetary_amount' => '400.00',
            'transaction_type' => 'credit',
            'user_uuid' => '12ebaf73-7722-4013-b31f-9450a4105492',
        ]);

        $expected = [
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
            'monetary_amount' => '400.00',
            'transaction_type' => 'credit',
        ];

        $this->assertEquals($expected, $historyView->jsonSerialize());
    }
}
