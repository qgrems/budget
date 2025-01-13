<?php

namespace App\Tests\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Projections\BudgetEnvelopeProjection;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeProjectionTest extends TestCase
{
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;
    private BudgetEnvelopeProjection $budgetEnvelopeProjection;

    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->budgetEnvelopeProjection = new BudgetEnvelopeProjection($this->envelopeViewRepository);
    }

    public function testHandleEnvelopeCreatedEvent(): void
    {
        $event = new BudgetEnvelopeCreatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '1ced5c7e-fd3a-4a36-808e-75ddc478f67b', 'Test', '1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BudgetEnvelopeView $view) use ($event) {
                return $view->uuid === $event->aggregateId
                    && $view->createdAt == $event->occurredOn
                    && $view->updatedAt == \DateTime::createFromImmutable($event->occurredOn)
                    && false === $view->isDeleted
                    && $view->targetedAmount === $event->targetedAmount
                    && '0.00' === $view->currentAmount
                    && $view->name === $event->name
                    && $view->userUuid === $event->userId;
            }));

        $this->budgetEnvelopeProjection->__invoke($event);
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

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
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

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedEvent(): void
    {
        $event = new BudgetEnvelopeRenamedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Test');
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeRenamedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Test');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedEvent(): void
    {
        $event = new BudgetEnvelopeDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b', true);
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b', true);

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeTargetedAmountUpdatedEvent(): void
    {
        $event = new BudgetEnvelopeTargetedAmountUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '1000.00');
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeTargetedAmountUpdatedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeTargetedAmountUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeRewoundEvent(): void
    {
        $event = new BudgetEnvelopeRewoundEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            '2024-01-01 00:00:00',
            false,
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeRewoundWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeRewoundEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            '2024-01-01 00:00:00',
            false,
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeReplayedEvent(): void
    {
        $event = new BudgetEnvelopeReplayedEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            '2024-01-01 00:00:00',
            false,
        );
        $envelopeView = BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent(
            new BudgetEnvelopeCreatedEvent(
                'b7e685be-db83-4866-9f85-102fac30a50b',
                '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
                'Test',
                '1000.00',
            ),
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleBudgetEnvelopeReplayedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeReplayedEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            '1ced5c7e-fd3a-4a36-808e-75ddc478f67b',
            'Test',
            '1000.00',
            '0.00',
            '2024-01-01 00:00:00',
            false,
        );

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId, 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }
}
