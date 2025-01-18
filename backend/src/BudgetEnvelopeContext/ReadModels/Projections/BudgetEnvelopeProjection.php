<?php

namespace App\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountUpdatedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;

final readonly class BudgetEnvelopeProjection
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeCreatedDomainEvent::class => $this->handleBudgetEnvelopeCreatedDomainEvent($event),
            BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeRenamedDomainEvent::class => $this->handleBudgetEnvelopeNamedDomainEvent($event),
            BudgetEnvelopeDeletedDomainEvent::class => $this->handleBudgetEnvelopeDeletedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->handleBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->handleBudgetEnvelopeReplayedDomainEvent($event),
            BudgetEnvelopeTargetedAmountUpdatedDomainEvent::class => $this->handleBudgetEnvelopeTargetedAmountUpdatedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetEnvelopeCreatedDomainEvent(BudgetEnvelopeCreatedDomainEvent $event): void
    {
        $this->budgetEnvelopeViewRepository->save(BudgetEnvelopeView::fromBudgetEnvelopeCreatedDomainEvent($event));
    }

    private function handleBudgetEnvelopeCreditedDomainEvent(
        BudgetEnvelopeCreditedDomainEvent $budgetEnvelopeCreditedDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeCreditedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeCreditedDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeDebitedDomainEvent(
        BudgetEnvelopeDebitedDomainEvent $budgetEnvelopeDebitedDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeDebitedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeDebitedDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeNamedDomainEvent(
        BudgetEnvelopeRenamedDomainEvent $budgetEnvelopeRenamedDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeRenamedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeRenamedDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeDeletedDomainEvent(
        BudgetEnvelopeDeletedDomainEvent $budgetEnvelopeDeletedDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeDeletedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeDeletedDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeRewoundDomainEvent(
        BudgetEnvelopeRewoundDomainEvent $budgetEnvelopeRewoundDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeRewoundDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeRewoundDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeReplayedDomainEvent(
        BudgetEnvelopeReplayedDomainEvent $budgetEnvelopeReplayedDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeReplayedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeReplayedDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }

    private function handleBudgetEnvelopeTargetedAmountUpdatedDomainEvent(
        BudgetEnvelopeTargetedAmountUpdatedDomainEvent $budgetEnvelopeTargetedAmountUpdatedDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeTargetedAmountUpdatedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeTargetedAmountUpdatedDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
    }
}
