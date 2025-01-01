<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Application\Handlers\QueryHandlers;

use App\BudgetEnvelopeManagement\Application\Handlers\QueryHandlers\GetABudgetEnvelopeWithItsHistoryQueryHandler;
use App\BudgetEnvelopeManagement\Application\Queries\GetABudgetEnvelopeWithItsHistoryQuery;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\ReadModels\Projections\BudgetEnvelopeProjection;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetABudgetEnvelopeWithItsHistoryQueryHandlerTest extends TestCase
{
    private GetABudgetEnvelopeWithItsHistoryQueryHandler $getABudgetEnvelopeWithItsHistoryQueryHandler;
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);

        $this->getABudgetEnvelopeWithItsHistoryQueryHandler = new GetABudgetEnvelopeWithItsHistoryQueryHandler(
            $this->envelopeViewRepository,
        );
    }

    public function testGetABudgetEnvelopeWithItsHistorySuccess(): void
    {
        $envelopeView = BudgetEnvelopeView::createFromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'target_budget' => '300.00',
                'current_budget' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'updated_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'is_deleted' => false,
            ],
        );
        $getABudgetEnvelopeWithItsHistoryQuery = new GetABudgetEnvelopeWithItsHistoryQuery(
            $envelopeView->getUuid(),
            'd26cc02e-99e7-428c-9d61-572dff3f84a7',
        );
        $envelopeHistoryCreatedAt = new \DateTimeImmutable();
        $envelopeCreatedAt = new \DateTime()->format('Y-m-d H:i:s');

        $this->envelopeViewRepository->expects($this->once())->method('findOneEnvelopeWithHistoryBy')
            ->willReturn(
                [
                    'envelope' => [
                        'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                        'name' => 'Electricity',
                        'target_budget' => '300.00',
                        'current_budget' => '150.00',
                        'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                        'created_at' => $envelopeCreatedAt,
                        'updated_at' => $envelopeCreatedAt,
                        'is_deleted' => false,
                    ],
                    'history' => [
                        [
                            'transaction_type' => BudgetEnvelopeProjection::DEBIT,
                            'aggregate_id' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                            'monetary_amount' => '150.00',
                            'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                            'created_at' => $envelopeHistoryCreatedAt,
                        ],
                    ],
                ],
            );

        $envelopeWithHistory = $this->getABudgetEnvelopeWithItsHistoryQueryHandler->__invoke($getABudgetEnvelopeWithItsHistoryQuery);

        $this->assertEquals([
            'envelope' => [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'target_budget' => '300.00',
                'current_budget' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => $envelopeCreatedAt,
                'updated_at' => $envelopeCreatedAt,
                'is_deleted' => false,
            ],
            'history' => [
                [
                    'transaction_type' => BudgetEnvelopeProjection::DEBIT,
                    'aggregate_id' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                    'monetary_amount' => '150.00',
                    'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                    'created_at' => $envelopeHistoryCreatedAt,
                ],
            ],
        ], $envelopeWithHistory);
    }

    public function testGetABudgetEnvelopeWithItsHistoryReturnsNull(): void
    {
        $envelopeView = BudgetEnvelopeView::createFromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'Electricity',
                'target_budget' => '300.00',
                'current_budget' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'updated_at' => new \DateTime()->format('Y-m-d H:i:s'),
                'is_deleted' => false,
            ],
        );
        $getABudgetEnvelopeWithItsHistoryQuery = new GetABudgetEnvelopeWithItsHistoryQuery(
            $envelopeView->getUuid(),
            'd26cc02e-99e7-428c-9d61-572dff3f84a7',
        );

        $this->envelopeViewRepository->expects($this->once())->method('findOneEnvelopeWithHistoryBy')->willReturn([]);
        $this->expectException(BudgetEnvelopeNotFoundException::class);
        $this->expectExceptionMessage('envelopes.notFound');

        $this->getABudgetEnvelopeWithItsHistoryQueryHandler->__invoke($getABudgetEnvelopeWithItsHistoryQuery);
    }
}
