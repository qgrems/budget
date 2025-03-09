<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanCurrencyChangedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanIncomeRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanNeedRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanSavingRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAddedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantAdjustedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanWantRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanCurrencyChangedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanGeneratedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanGeneratedWithOneThatAlreadyExistsNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
use App\Libraries\FluxCapacitor\Ports\DomainEventInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;

final readonly class BudgetPlanProjection
{
    public function __construct(
        private BudgetPlanViewRepositoryInterface $budgetPlanViewRepository,
        private PublisherInterface $publisher,
    ) {
    }

    public function __invoke(DomainEventInterface $event): void
    {
        match($event::class) {
            BudgetPlanGeneratedDomainEvent::class => $this->handleBudgetPlanGeneratedDomainEvent($event),
            BudgetPlanRemovedDomainEvent::class => $this->handleBudgetPlanRemovedDomainEvent($event),
            BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent::class => $this->handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent($event),
            BudgetPlanCurrencyChangedDomainEvent::class => $this->handleBudgetPlanCurrencyChangedDomainEvent($event),
            BudgetPlanIncomeAddedDomainEvent::class => $this->handleBudgetPlanIncomeAddedDomainEvent($event),
            BudgetPlanSavingAddedDomainEvent::class => $this->handleBudgetPlanSavingAddedDomainEvent($event),
            BudgetPlanNeedAddedDomainEvent::class => $this->handleBudgetPlanNeedAddedDomainEvent($event),
            BudgetPlanWantAddedDomainEvent::class => $this->handleBudgetPlanWantAddedDomainEvent($event),
            BudgetPlanIncomeAdjustedDomainEvent::class => $this->handleBudgetPlanIncomeAdjustedDomainEvent($event),
            BudgetPlanNeedAdjustedDomainEvent::class => $this->handleBudgetPlanNeedAdjustedDomainEvent($event),
            BudgetPlanSavingAdjustedDomainEvent::class => $this->handleBudgetPlanSavingAdjustedDomainEvent($event),
            BudgetPlanWantAdjustedDomainEvent::class => $this->handleBudgetPlanWantAdjustedDomainEvent($event),
            BudgetPlanIncomeRemovedDomainEvent::class => $this->handleBudgetPlanIncomeRemovedDomainEvent($event),
            BudgetPlanWantRemovedDomainEvent::class => $this->handleBudgetPlanWantRemovedDomainEvent($event),
            BudgetPlanNeedRemovedDomainEvent::class => $this->handleBudgetPlanNeedRemovedDomainEvent($event),
            BudgetPlanSavingRemovedDomainEvent::class => $this->handleBudgetPlanSavingRemovedDomainEvent($event),
            default => null,
        };
    }

    private function handleBudgetPlanGeneratedDomainEvent(
        BudgetPlanGeneratedDomainEvent $budgetPlanGeneratedDomainEvent,
    ): void {
        $this->budgetPlanViewRepository->save(
            BudgetPlanView::fromBudgetPlanGeneratedDomainEvent($budgetPlanGeneratedDomainEvent),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetPlanGeneratedNotificationEvent::fromDomainEvent($budgetPlanGeneratedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
        BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
    ): void {
        $this->budgetPlanViewRepository->save(
            BudgetPlanView::fromBudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent(
                $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
            ),
        );
        try {
            $this->publisher->publishNotificationEvents(
                [
                    BudgetPlanGeneratedWithOneThatAlreadyExistsNotificationEvent::fromDomainEvent(
                        $budgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent,
                    ),
                ],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanRemovedDomainEvent(
        BudgetPlanRemovedDomainEvent $budgetPlanRemovedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanRemovedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanRemovedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetPlanRemovedNotificationEvent::fromDomainEvent($budgetPlanRemovedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanCurrencyChangedDomainEvent(
        BudgetPlanCurrencyChangedDomainEvent $budgetPlanCurrencyChangedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanCurrencyChangedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanCurrencyChangedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
        try {
            $this->publisher->publishNotificationEvents(
                [BudgetPlanCurrencyChangedNotificationEvent::fromDomainEvent($budgetPlanCurrencyChangedDomainEvent)],
            );
        } catch (\Exception $e) {
        }
    }

    private function handleBudgetPlanIncomeAddedDomainEvent(
        BudgetPlanIncomeAddedDomainEvent $budgetPlanIncomeAddedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanIncomeAddedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanIncomeAddedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingAddedDomainEvent(
        BudgetPlanSavingAddedDomainEvent $budgetPlanSavingAddedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanSavingAddedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanSavingAddedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedAddedDomainEvent(
        BudgetPlanNeedAddedDomainEvent $budgetPlanNeedAddedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanNeedAddedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanNeedAddedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantAddedDomainEvent(
        BudgetPlanWantAddedDomainEvent $budgetPlanWantAddedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanWantAddedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanWantAddedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanIncomeAdjustedDomainEvent(
        BudgetPlanIncomeAdjustedDomainEvent $budgetPlanIncomeAdjustedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanIncomeAdjustedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanIncomeAdjustedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedAdjustedDomainEvent(
        BudgetPlanNeedAdjustedDomainEvent $budgetPlanNeedAdjustedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanNeedAdjustedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanNeedAdjustedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingAdjustedDomainEvent(
        BudgetPlanSavingAdjustedDomainEvent $budgetPlanSavingAdjustedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanSavingAdjustedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanSavingAdjustedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantAdjustedDomainEvent(
        BudgetPlanWantAdjustedDomainEvent $budgetPlanWantAdjustedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanWantAdjustedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanWantAdjustedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanIncomeRemovedDomainEvent(
        BudgetPlanIncomeRemovedDomainEvent $budgetPlanIncomeRemovedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanIncomeRemovedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanIncomeRemovedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanWantRemovedDomainEvent(
        BudgetPlanWantRemovedDomainEvent $budgetPlanWantRemovedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanWantRemovedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanWantRemovedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanNeedRemovedDomainEvent(
        BudgetPlanNeedRemovedDomainEvent $budgetPlanNeedRemovedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanNeedRemovedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanNeedRemovedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }

    private function handleBudgetPlanSavingRemovedDomainEvent(
        BudgetPlanSavingRemovedDomainEvent $budgetPlanSavingRemovedDomainEvent,
    ): void {
        $budgetPlanView = $this->budgetPlanViewRepository->findOneBy(
            ['uuid' => $budgetPlanSavingRemovedDomainEvent->aggregateId, 'is_deleted' => false],
        );

        if (!$budgetPlanView instanceof BudgetPlanViewInterface) {
            return;
        }

        $budgetPlanView->fromEvent($budgetPlanSavingRemovedDomainEvent);
        $this->budgetPlanViewRepository->save($budgetPlanView);
    }
}
