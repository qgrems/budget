<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeReplayedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRewoundDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeTargetedAmountChangedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeAddedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeCreditedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeDebitedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeDeletedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeRenamedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeReplayedNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeRewoundNotificationEvent;
use App\BudgetEnvelopeContext\Infrastructure\Events\Notifications\BudgetEnvelopeTargetedAmountChangedNotificationEvent;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetEnvelopeProjection
{
    public function __construct(
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeAddedDomainEvent::class => $this->handleBudgetEnvelopeAddedDomainEvent($event),
            BudgetEnvelopeCreditedDomainEvent::class => $this->handleBudgetEnvelopeCreditedDomainEvent($event),
            BudgetEnvelopeDebitedDomainEvent::class => $this->handleBudgetEnvelopeDebitedDomainEvent($event),
            BudgetEnvelopeRenamedDomainEvent::class => $this->handleBudgetEnvelopeNamedDomainEvent($event),
            BudgetEnvelopeDeletedDomainEvent::class => $this->handleBudgetEnvelopeDeletedDomainEvent($event),
            BudgetEnvelopeRewoundDomainEvent::class => $this->handleBudgetEnvelopeRewoundDomainEvent($event),
            BudgetEnvelopeReplayedDomainEvent::class => $this->handleBudgetEnvelopeReplayedDomainEvent($event),
            BudgetEnvelopeTargetedAmountChangedDomainEvent::class => $this->handleBudgetEnvelopeTargetedAmountChangedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetEnvelopeAddedDomainEvent(
        BudgetEnvelopeAddedDomainEvent $budgetEnvelopeAddedDomainEvent,
    ): void {
        $this->budgetEnvelopeViewRepository->save(
            BudgetEnvelopeView::fromBudgetEnvelopeAddedDomainEvent($budgetEnvelopeAddedDomainEvent),
        );
        $this->publisher->publishNotificationEvents(
            [BudgetEnvelopeAddedNotificationEvent::fromDomainEvent($budgetEnvelopeAddedDomainEvent)],
        );
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
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeCreditedNotificationEvent::fromDomainEvent($budgetEnvelopeCreditedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
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
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeDebitedNotificationEvent::fromDomainEvent($budgetEnvelopeDebitedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
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
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeRenamedNotificationEvent::fromDomainEvent($budgetEnvelopeRenamedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
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
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeDeletedNotificationEvent::fromDomainEvent($budgetEnvelopeDeletedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
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
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeRewoundNotificationEvent::fromDomainEvent($budgetEnvelopeRewoundDomainEvent)],
            );
        } catch (\Exception $e) {
        }
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
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetEnvelopeReplayedNotificationEvent::fromDomainEvent($budgetEnvelopeReplayedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetEnvelopeTargetedAmountChangedDomainEvent(
        BudgetEnvelopeTargetedAmountChangedDomainEvent $budgetEnvelopeTargetedAmountChangedDomainEvent,
    ): void {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeTargetedAmountChangedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeView->fromEvent($budgetEnvelopeTargetedAmountChangedDomainEvent);
        $this->budgetEnvelopeViewRepository->save($budgetEnvelopeView);
        try {
            $this->publisher->publishNotificationEvents([
                BudgetEnvelopeTargetedAmountChangedNotificationEvent::fromDomainEvent(
                    $budgetEnvelopeTargetedAmountChangedDomainEvent,
                ),
            ]);
        } catch (\Exception $e) {
        }
    }
}
