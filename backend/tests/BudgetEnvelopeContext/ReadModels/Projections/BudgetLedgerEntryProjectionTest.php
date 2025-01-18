<?php

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Projections\BudgetEnvelopeLedgerEntryProjection;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeLedgerEntryView;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetLedgerEntryProjectionTest extends TestCase
{
    private BudgetEnvelopeViewRepositoryInterface&MockObject $budgetEnvelopeViewRepository;
    private BudgetEnvelopeLedgerEntryViewRepositoryInterface&MockObject $budgetEnvelopeLedgerEntryViewRepository;
    private BudgetEnvelopeLedgerEntryProjection $budgetEnvelopeLedgerEntryProjection;

    protected function setUp(): void
    {
        $this->budgetEnvelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->budgetEnvelopeLedgerEntryViewRepository = $this->createMock(BudgetEnvelopeLedgerEntryViewRepositoryInterface::class);
        $this->budgetEnvelopeLedgerEntryProjection = new BudgetEnvelopeLedgerEntryProjection(
            $this->budgetEnvelopeLedgerEntryViewRepository,
            $this->budgetEnvelopeViewRepository,
        );
    }

    public function testHandleEnvelopeCreditedEvent(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedDomainEvent(
            new BudgetEnvelopeCreatedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );
        $envelopeHistory = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeCreditedDomainEvent(
            $event,
            $envelopeView->userUuid,
        );

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);
        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
        );

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);
        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->never())
            ->method('save');

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedEvent(): void
    {
        $event = new BudgetEnvelopeDebitedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedDomainEvent(
            new BudgetEnvelopeCreatedDomainEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );
        $envelopeView->fromEvent(new BudgetEnvelopeCreditedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
        ));

        $envelopeHistory = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeDebitedDomainEvent(
            $event,
            $envelopeView->userUuid,
        );

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);
        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDebitedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            '500.00',
        );

        $this->budgetEnvelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);
        $this->budgetEnvelopeLedgerEntryViewRepository->expects($this->never())
            ->method('save');

        $this->budgetEnvelopeLedgerEntryProjection->__invoke($event);
    }
}
