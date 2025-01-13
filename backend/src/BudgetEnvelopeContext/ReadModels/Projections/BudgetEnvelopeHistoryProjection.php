<?php

namespace App\BudgetEnvelopeContext\ReadModels\Projections;

use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeHistoryViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewInterface;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeHistoryView;
use App\SharedContext\Domain\Ports\Inbound\EventInterface;

final readonly class BudgetEnvelopeHistoryProjection
{
    public function __construct(
        private BudgetEnvelopeHistoryViewRepositoryInterface $budgetEnvelopeHistoryViewRepository,
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    public function __invoke(EventInterface $event): void
    {
        match($event::class) {
            BudgetEnvelopeCreditedEvent::class => $this->handleEnvelopeCreditedEvent($event),
            BudgetEnvelopeDebitedEvent::class => $this->handleEnvelopeDebitedEvent($event),
            default => null,
        };
    }

    private function handleEnvelopeCreditedEvent(BudgetEnvelopeCreditedEvent $budgetEnvelopeCreditedEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeCreditedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeHistoryView = BudgetEnvelopeHistoryView::fromBudgetEnvelopeCreditedEvent(
            $budgetEnvelopeCreditedEvent,
            $budgetEnvelopeView->userUuid,
        );

        $this->budgetEnvelopeHistoryViewRepository->save($budgetEnvelopeHistoryView);
    }

    private function handleEnvelopeDebitedEvent(BudgetEnvelopeDebitedEvent $budgetEnvelopeDebitedEvent): void
    {
        $budgetEnvelopeView = $this->budgetEnvelopeViewRepository->findOneBy(
            ['uuid' => $budgetEnvelopeDebitedEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetEnvelopeView instanceof BudgetEnvelopeViewInterface) {
            return;
        }

        $budgetEnvelopeHistoryView = BudgetEnvelopeHistoryView::fromBudgetEnvelopeDebitedEvent(
            $budgetEnvelopeDebitedEvent,
            $budgetEnvelopeView->userUuid,
        );

        $this->budgetEnvelopeHistoryViewRepository->save($budgetEnvelopeHistoryView);
    }
}
