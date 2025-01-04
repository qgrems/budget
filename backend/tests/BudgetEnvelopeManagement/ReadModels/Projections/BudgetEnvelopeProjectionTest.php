<?php

namespace App\Tests\BudgetEnvelopeManagement\ReadModels\Projections;

use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewRepositoryInterface;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\ReadModels\Projections\BudgetEnvelopeProjection;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeHistoryView;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BudgetEnvelopeProjectionTest extends TestCase
{
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;
    private BudgetEnvelopeHistoryViewRepositoryInterface&MockObject $envelopeHistoryViewRepository;
    private BudgetEnvelopeProjection $budgetEnvelopeProjection;

    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->envelopeHistoryViewRepository = $this->createMock(BudgetEnvelopeHistoryViewRepositoryInterface::class);
        $this->budgetEnvelopeProjection = new BudgetEnvelopeProjection($this->envelopeViewRepository, $this->envelopeHistoryViewRepository);
    }

    public function testHandleEnvelopeCreatedEvent(): void
    {
        $event = new BudgetEnvelopeCreatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '1ced5c7e-fd3a-4a36-808e-75ddc478f67b', 'Test', '1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (BudgetEnvelopeView $view) use ($event) {
                return $view->getUuid() === $event->getAggregateId()
                    && $view->getCreatedAt() == $event->occurredOn()
                    && $view->getUpdatedAt() == \DateTime::createFromImmutable($event->occurredOn())
                    && false === $view->isDeleted()
                    && $view->getTargetedAmount() === $event->getTargetedAmount()
                    && '0.00' === $view->getCurrentAmount()
                    && $view->getName() === $event->getName()
                    && $view->getUserUuid() === $event->getUserId();
            }));

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedEvent(): void
    {
        $event = new BudgetEnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentAmount('1000.00');
        $envelopeView->setUserUuid('1ced5c7e-fd3a-4a36-808e-75ddc478f67b');
        $envelopeHistory = new BudgetEnvelopeHistoryView();
        $envelopeHistory
            ->setTransactionType(BudgetEnvelopeProjection::CREDIT)
            ->setAggregateId($event->getAggregateId())
            ->setMonetaryAmount($event->getCreditMoney())
            ->setUserUuid($envelopeView->getUserUuid())
            ->setCreatedAt($event->occurredOn())
        ;

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);
        $this->envelopeHistoryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentAmount('1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedEvent(): void
    {
        $event = new BudgetEnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentAmount('1000.00');
        $envelopeView->setUserUuid('1ced5c7e-fd3a-4a36-808e-75ddc478f67b');
        $envelopeHistory = new BudgetEnvelopeHistoryView();
        $envelopeHistory
            ->setTransactionType(BudgetEnvelopeProjection::DEBIT)
            ->setAggregateId($event->getAggregateId())
            ->setMonetaryAmount($event->getDebitMoney())
            ->setUserUuid($envelopeView->getUserUuid())
            ->setCreatedAt($event->occurredOn())
        ;

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);
        $this->envelopeHistoryViewRepository->expects($this->once())
            ->method('save')
            ->with($envelopeHistory);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentAmount('1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedEvent(): void
    {
        $event = new BudgetEnvelopeRenamedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Test');
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeRenamedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Test');
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedEvent(): void
    {
        $event = new BudgetEnvelopeDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b', true);
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new BudgetEnvelopeDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b', true);
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->budgetEnvelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeTargetedAmountUpdatedEvent(): void
    {
        $event = new BudgetEnvelopeTargetedAmountUpdatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '1000.00');
        $envelopeView = new BudgetEnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setTargetedAmount('500.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->budgetEnvelopeProjection->__invoke($event);
    }
}
