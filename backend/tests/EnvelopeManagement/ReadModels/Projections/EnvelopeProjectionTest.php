<?php

namespace App\Tests\EnvelopeManagement\ReadModels\Projections;

use App\EnvelopeManagement\Domain\Events\EnvelopeCreatedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeCreditedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeDebitedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeDeletedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeNamedEvent;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\EnvelopeManagement\ReadModels\Projections\EnvelopeProjection;
use App\EnvelopeManagement\ReadModels\Views\EnvelopeView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnvelopeProjectionTest extends TestCase
{
    private EnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;
    private EnvelopeProjection $envelopeProjection;

    protected function setUp(): void
    {
        $this->envelopeViewRepository = $this->createMock(EnvelopeViewRepositoryInterface::class);
        $this->envelopeProjection = new EnvelopeProjection($this->envelopeViewRepository);
    }

    public function testHandleEnvelopeCreatedEvent(): void
    {
        $event = new EnvelopeCreatedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '1ced5c7e-fd3a-4a36-808e-75ddc478f67b', 'Test', '1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (EnvelopeView $view) use ($event) {
                return $view->getUuid() === $event->getAggregateId() &&
                    $view->getCreatedAt() == $event->occurredOn() &&
                    $view->getUpdatedAt() == \DateTime::createFromImmutable($event->occurredOn()) &&
                    $view->isDeleted() === false &&
                    $view->getTargetBudget() === $event->getTargetBudget() &&
                    $view->getCurrentBudget() === '0.00' &&
                    $view->getName() === $event->getName() &&
                    $view->getUserUuid() === $event->getUserId();
            }));

        $this->envelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedEvent(): void
    {
        $event = new EnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentBudget('1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->envelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeCreditedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new EnvelopeCreditedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentBudget('1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->envelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedEvent(): void
    {
        $event = new EnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentBudget('1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->envelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDebitedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new EnvelopeDebitedEvent('b7e685be-db83-4866-9f85-102fac30a50b', '500.00');
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());
        $envelopeView->setCurrentBudget('1000.00');

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->envelopeProjection->__invoke($event);
    }


    public function testHandleEnvelopeNamedEvent(): void
    {
        $event = new EnvelopeNamedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Test');
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->envelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeNamedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new EnvelopeNamedEvent('b7e685be-db83-4866-9f85-102fac30a50b', 'Test');
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->envelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedEvent(): void
    {
        $event = new EnvelopeDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b', true);
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn($envelopeView);

        $this->envelopeProjection->__invoke($event);
    }

    public function testHandleEnvelopeDeletedWithEnvelopeThatDoesNotExist(): void
    {
        $event = new EnvelopeDeletedEvent('b7e685be-db83-4866-9f85-102fac30a50b', true);
        $envelopeView = new EnvelopeView();
        $envelopeView->setUuid($event->getAggregateId());

        $this->envelopeViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->getAggregateId(), 'is_deleted' => false])
            ->willReturn(null);

        $this->envelopeProjection->__invoke($event);
    }
}
