<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeLedgerEntryViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeLedgerEntryView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class BudgetEnvelopeLedgerEntryProjection
{
    public function __construct(
        private BudgetEnvelopeLedgerEntryViewRepositoryInterface $budgetEnvelopeLedgerEntryViewRepository,
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->handleBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->handleBudgetEnvelopeReplayedDomainEvent($event),
            default => null,
        };
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

        $budgetEnvelopeLedgerEntryView = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeCreditedDomainEvent(
            $budgetEnvelopeCreditedDomainEvent,
            $budgetEnvelopeView->userUuid,
        );

        $this->budgetEnvelopeLedgerEntryViewRepository->save($budgetEnvelopeLedgerEntryView);
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

        $budgetEnvelopeLedgerEntryView = BudgetEnvelopeLedgerEntryView::fromBudgetEnvelopeDebitedDomainEvent(
            $budgetEnvelopeDebitedDomainEvent,
            $budgetEnvelopeView->userUuid,
        );

        $this->budgetEnvelopeLedgerEntryViewRepository->save($budgetEnvelopeLedgerEntryView);
    }

    private function handleBudgetEnvelopeRewoundDomainEvent(
        BudgetEnvelopeRewoundDomainEvent $budgetEnvelopeRewoundDomainEvent,
    ): void {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($budgetEnvelopeRewoundDomainEvent->aggregateId);

        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $budgetEnvelopeRewoundDomainEvent->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent::class, BudgetEnvelopeDebitedDomainEvent::class],
            $budgetEnvelopeRewoundDomainEvent->desiredDateTime,
        );

        foreach ($budgetEnvelopeEvents as $budgetEnvelopeEvent) {
            match ($budgetEnvelopeEvent['type']) {
                BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent(
                    BudgetEnvelopeCreditedDomainEvent::fromArray(
                        (json_decode($budgetEnvelopeEvent['payload'], true)),
                    ),
                ),
                BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent(
                    BudgetEnvelopeDebitedDomainEvent::fromArray(
                        (json_decode($budgetEnvelopeEvent['payload'], true)),
                    ),
                ),
                default => null,
            };
        }
    }

    private function handleBudgetEnvelopeReplayedDomainEvent(
        BudgetEnvelopeReplayedDomainEvent $budgetEnvelopeReplayedDomainEvent,
    ): void {
        $this->budgetEnvelopeLedgerEntryViewRepository->delete($budgetEnvelopeReplayedDomainEvent->aggregateId);

        $budgetEnvelopeEvents = $this->eventSourcedRepository->getByDomainEvents(
            $budgetEnvelopeReplayedDomainEvent->aggregateId,
            [BudgetEnvelopeCreditedDomainEvent::class, BudgetEnvelopeDebitedDomainEvent::class],
            $budgetEnvelopeReplayedDomainEvent->occurredOn,
        );

        foreach ($budgetEnvelopeEvents as $budgetEnvelopeEvent) {
            match ($budgetEnvelopeEvent['type']) {
                BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent(
                    BudgetEnvelopeCreditedDomainEvent::fromArray(
                        (json_decode($budgetEnvelopeEvent['payload'], true)),
                    ),
                ),
                BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent(
                    BudgetEnvelopeDebitedDomainEvent::fromArray(
                        (json_decode($budgetEnvelopeEvent['payload'], true)),
                    ),
                ),
                default => null,
            };
        }
    }
}
