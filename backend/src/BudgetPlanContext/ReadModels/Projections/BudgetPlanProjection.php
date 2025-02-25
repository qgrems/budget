<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\ReadModels\Projections;

use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanGeneratedWithOneThatAlreadyExistsDomainEvent;
use App\BudgetPlanContext\Domain\Events\BudgetPlanRemovedDomainEvent;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewInterface;
use App\BudgetPlanContext\Domain\Ports\Inbound\BudgetPlanViewRepositoryInterface;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanGeneratedNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanGeneratedWithOneThatAlreadyExistsNotificationEvent;
use App\BudgetPlanContext\Infrastructure\Events\Notifications\BudgetPlanRemovedNotificationEvent;
use App\BudgetPlanContext\ReadModels\Views\BudgetPlanView;
use App\SharedContext\Domain\Ports\Inbound\DomainEventInterface;
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
}
