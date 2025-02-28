<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanIncomeEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanIncomeAddedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanIncomeAdjustedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanIncomeRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanIncomeEntryView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanIncomeEntryProjection
{
    public function __construct(
        private BudgetPlanIncomeEntryViewRepositoryInterface $budgetPlanIncomeEntryViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanIncomeAddedDomainEvent::class => $this->handleBudgetPlanIncomeAddedDomainEvent($event),
            BudgetPlanIncomeAdjustedDomainEvent::class => $this->handleBudgetPlanIncomeAdjustedDomainEvent($event),
            BudgetPlanIncomeRemovedDomainEvent::class => $this->handleBudgetPlanIncomeRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedDomainEvent->incomes as $income) {
            $this->budgetPlanIncomeEntryViewRepository->save(
                BudgetPlanIncomeEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $income,
                    $budgetPlanGeneratedDomainEvent->aggregateId,
                    $budgetPlanGeneratedDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanIncomeAddedNotificationEvent::fromBudgetPlanGeneratedDomainEvent(
                            $budgetPlanGeneratedDomainEvent,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->incomes as $income) {
            $this->budgetPlanIncomeEntryViewRepository->save(
                BudgetPlanIncomeEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $income,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->aggregateId,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanIncomeAddedNotificationEvent::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanIncomeAddedDomainEvent(
        BudgetPlanIncomeAddedDomainEvent $budgetPlanIncomeAddedDomainEvent,
    ): void {
        $this->budgetPlanIncomeEntryViewRepository->save(
            BudgetPlanIncomeEntryView::fromBudgetPlanIncomeAddedDomainEvent($budgetPlanIncomeAddedDomainEvent),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanIncomeAddedNotificationEvent::fromBudgetPlanIncomeAddedDomainEvent(
                        $budgetPlanIncomeAddedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanIncomeAdjustedDomainEvent(
        BudgetPlanIncomeAdjustedDomainEvent $budgetPlanIncomeAdjustedDomainEvent,
    ): void {
        $budgetPlanIncomeView = $this->budgetPlanIncomeEntryViewRepository->findOneByUuid(
            $budgetPlanIncomeAdjustedDomainEvent->uuid,
        );

        if (!$budgetPlanIncomeView instanceof BudgetPlanIncomeEntryViewInterface) {
            return;
        }

        $budgetPlanIncomeView->fromEvent($budgetPlanIncomeAdjustedDomainEvent);
        $this->budgetPlanIncomeEntryViewRepository->save($budgetPlanIncomeView);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanIncomeAdjustedNotificationEvent::fromBudgetPlanIncomeAdjustedDomainEvent(
                        $budgetPlanIncomeAdjustedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent(
        BudgetPlanIncomeRemovedDomainEvent $budgetPlanIncomeRemovedDomainEvent,
    ): void {
        $this->budgetPlanIncomeEntryViewRepository->delete($budgetPlanIncomeRemovedDomainEvent->uuid);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanIncomeRemovedNotificationEvent::fromDomainEvent(
                        $budgetPlanIncomeRemovedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }
}
