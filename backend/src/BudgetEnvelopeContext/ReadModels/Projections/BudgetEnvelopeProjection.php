<?php

namespace App\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final readonly class BudgetEnvelopeProjection
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    public function __invoke(EventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeCreatedEvent::class => $this->handleBudgetEnvelopeCreatedEvent($event),
            BudgetEnvelopeCreditedEvent::class => $this->handleBudgetEnvelopeCreditedEvent($event),
            BudgetEnvelopeDebitedEvent::class => $this->handleBudgetEnvelopeDebitedEvent($event),
            BudgetEnvelopeRenamedEvent::class => $this->handleBudgetEnvelopeNamedEvent($event),
            BudgetEnvelopeDeletedEvent::class => $this->handleBudgetEnvelopeDeletedEvent($event),
            BudgetEnvelopeRewoundEvent::class => $this->handleBudgetEnvelopeRewoundEvent($event),
            BudgetEnvelopeReplayedEvent::class => $this->handleBudgetEnvelopeReplayedEvent($event),
            BudgetEnvelopeTargetedAmountUpdatedEvent::class => $this->handleBudgetEnvelopeTargetedAmountUpdatedEvent($event),
            default => null,
        };
    }

    private function handleBudgetEnvelopeCreatedEvent(BudgetEnvelopeCreatedEvent $event): void
    {
        $this->budgetEnvelopeViewRepository->save(BudgetEnvelopeView::fromBudgetEnvelopeCreatedEvent($event));
    }

    private function handleBudgetEnvelopeCreditedEvent(BudgetEnvelopeCreditedEvent $budgetEnvelopeCreditedEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeCreditedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeCreditedEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeDebitedEvent(BudgetEnvelopeDebitedEvent $budgetEnvelopeDebitedEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeDebitedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeDebitedEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeNamedEvent(BudgetEnvelopeRenamedEvent $budgetEnvelopeRenamedEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeRenamedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeRenamedEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeDeletedEvent(BudgetEnvelopeDeletedEvent $budgetEnvelopeDeletedEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeDeletedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeDeletedEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeRewoundEvent(BudgetEnvelopeRewoundEvent $budgetEnvelopeRewoundEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeRewoundEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeRewoundEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeReplayedEvent(BudgetEnvelopeReplayedEvent $budgetEnvelopeReplayedEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeReplayedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeReplayedEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeTargetedAmountUpdatedEvent(
        BudgetEnvelopeTargetedAmountUpdatedEvent $budgetEnvelopeTargetedAmountUpdatedEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeTargetedAmountUpdatedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeTargetedAmountUpdatedEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }
}
