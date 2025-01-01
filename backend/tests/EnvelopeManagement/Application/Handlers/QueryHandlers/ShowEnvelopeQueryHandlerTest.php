<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Application\Handlers\QueryHandlers;

use App\EnvelopeManagement\Application\Handlers\QueryHandlers\ShowEnvelopeQueryHandler;
use App\EnvelopeManagement\Application\Queries\ShowEnvelopeQuery;
use App\EnvelopeManagement\Domain\Exceptions\EnvelopeNotFoundException;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\EnvelopeManagement\ReadModels\Projections\EnvelopeProjection;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeHistoryView;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShowEnvelopeQueryHandlerTest extends TestCase
{
    private ShowEnvelopeQueryHandler $showEnvelopeQueryHandler;
    private EnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(EnvelopeViewRepositoryInterface::class);

        $this->showEnvelopeQueryHandler = new ShowEnvelopeQueryHandler(
            $this->envelopeViewRepository,
        );
    }

    public function testShowEnvelopeSuccess(): void
    {
        $envelopeView = EnvelopeView::createFromRepository(
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
        $showEnvelopeQuery = new ShowEnvelopeQuery(
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
                            'transaction_type' => EnvelopeProjection::DEBIT,
                            'aggregate_id' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                            'monetary_amount' => '150.00',
                            'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                            'created_at' => $envelopeHistoryCreatedAt,
                        ],
                    ],
                ],
            );

        $envelopeWithHistory = $this->showEnvelopeQueryHandler->__invoke($showEnvelopeQuery);

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
                    'transaction_type' => EnvelopeProjection::DEBIT,
                    'aggregate_id' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                    'monetary_amount' => '150.00',
                    'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                    'created_at' => $envelopeHistoryCreatedAt,
                ],
            ],
        ], $envelopeWithHistory);
    }

    public function testShowEnvelopeReturnsNull(): void
    {
        $envelopeView = EnvelopeView::createFromRepository(
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
        $showEnvelopeQuery = new ShowEnvelopeQuery(
            $envelopeView->getUuid(),
            'd26cc02e-99e7-428c-9d61-572dff3f84a7',
        );

        $this->envelopeViewRepository->expects($this->once())->method('findOneEnvelopeWithHistoryBy')->willReturn([]);
        $this->expectException(EnvelopeNotFoundException::class);
        $this->expectExceptionMessage('envelopes.notFound');

        $this->showEnvelopeQueryHandler->__invoke($showEnvelopeQuery);
    }
}
