<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanSavingEntryViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanSavingAddedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanSavingAdjustedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanSavingRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanSavingEntryView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanSavingEntryProjection
{
    public function __construct(
        private BudgetPlanSavingEntryViewRepositoryInterface $budgetPlanSavingEntryViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanSavingAddedDomainEvent::class => $this->handleBudgetPlanSavingAddedDomainEvent($event),
            BudgetPlanSavingAdjustedDomainEvent::class => $this->handleBudgetPlanSavingAdjustedDomainEvent($event),
            BudgetPlanSavingRemovedDomainEvent::class => $this->handleBudgetPlanSavingRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        foreach ($budgetPlanGeneratedDomainEvent->savings as $saving) {
            $this->budgetPlanSavingEntryViewRepository->save(
                BudgetPlanSavingEntryView::fromArrayOnBudgetPlanGeneratedDomainEvent(
                    $saving,
                    $budgetPlanGeneratedDomainEvent->aggregateId,
                    $budgetPlanGeneratedDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanSavingAddedNotificationEvent::fromBudgetPlanGeneratedDomainEvent(
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
        foreach ($budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->savings as $saving) {
            $this->budgetPlanSavingEntryViewRepository->save(
                BudgetPlanSavingEntryView::fromArrayOnBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                    $saving,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->aggregateId,
                    $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent->occurredOn,
                ),
            );
            try {
                $this->publisher->publishNotificationEvents(
                    [
                        BudgetPlanSavingAddedNotificationEvent::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                            $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
                        ),
                    ],
                );
            } catch (\Exception $e) {
            }
        }
    }

    private function handleBudgetPlanSavingAddedDomainEvent(
        BudgetPlanSavingAddedDomainEvent $budgetPlanSavingAddedDomainEvent,
    ): void {
        $this->budgetPlanSavingEntryViewRepository->save(
            BudgetPlanSavingEntryView::fromBudgetPlanSavingAddedDomainEvent($budgetPlanSavingAddedDomainEvent),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanSavingAddedNotificationEvent::fromBudgetPlanSavingAddedDomainEvent(
                        $budgetPlanSavingAddedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanSavingAdjustedDomainEvent(
        BudgetPlanSavingAdjustedDomainEvent $budgetPlanSavingAdjustedDomainEvent,
    ): void {
        $budgetPlanSavingView = $this->budgetPlanSavingEntryViewRepository->findOneByUuid(
            $budgetPlanSavingAdjustedDomainEvent->uuid,
        );

        if (!$budgetPlanSavingView instanceof BudgetPlanSavingEntryViewInterface) {
            return;
        }

        $budgetPlanSavingView->fromEvent($budgetPlanSavingAdjustedDomainEvent);
        $this->budgetPlanSavingEntryViewRepository->save($budgetPlanSavingView);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanSavingAdjustedNotificationEvent::fromBudgetPlanSavingAdjustedDomainEvent(
                        $budgetPlanSavingAdjustedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanSavingRemovedDomainEvent(
        BudgetPlanSavingRemovedDomainEvent $budgetPlanSavingRemovedDomainEvent,
    ): void {
        $this->budgetPlanSavingEntryViewRepository->delete($budgetPlanSavingRemovedDomainEvent->uuid);
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanSavingRemovedNotificationEvent::fromDomainEvent(
                        $budgetPlanSavingRemovedDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }
}
