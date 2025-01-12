<?php

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Projections\BudgetEnvelopeHistoryProjection;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeHistoryView;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeHistoryProjectionTest extends TestCase
{
    private BudgetEnvelopeViewRepositoryInterface&MockObject $budgetEnvelopeViewRepository;
    private BudgetEnvelopeHistoryViewRepositoryInterface&MockObject $budgetEnvelopeHistoryViewRepository;
    private BudgetEnvelopeHistoryProjection $budgetEnvelopeHistoryProjection;

    protected function setUp(): void
    {
        $this->budgetEnvelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->budgetEnvelopeHistoryViewRepository = $this->createMock(BudgetEnvelopeHistoryViewRepositoryInterface::class);
        $this->budgetEnvelopeHistoryProjection = new BudgetEnvelopeHistoryProjection(
            $this->budgetEnvelopeHistoryViewRepository,
            $this->budgetEnvelopeViewRepository,
        );
    }

    public function testHandleEnvelopeCreditedEvent(): void
    {
        $event = new BudgetEnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );
        $envelopeHistory = BudgetEnvelopeHistoryView::fromBudgetEnvelopeCreditedEvent(
            $event,
            $envelopeView->userUuid,
        );

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);
        $this->budgetEnvelopeHistoryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeHistoryProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);
        $this->budgetEnvelopeHistoryViewRepository->expects($this->never())
            ->method('save');

        $this->budgetEnvelopeHistoryProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedEvent(): void
    {
        $event = new BudgetEnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );
        $envelopeView->fromEvent(new BudgetEnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00'));

        $envelopeHistory = BudgetEnvelopeHistoryView::fromBudgetEnvelopeDebitedEvent(
            $event,
            $envelopeView->userUuid,
        );

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);
        $this->budgetEnvelopeHistoryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeHistoryProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);
        $this->budgetEnvelopeHistoryViewRepository->expects($this->never())
            ->method('save');

        $this->budgetEnvelopeHistoryProjection->__invoke($event);
    }
}
