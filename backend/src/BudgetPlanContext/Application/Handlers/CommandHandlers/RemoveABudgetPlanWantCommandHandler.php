<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RemoveABudgetPlanWantCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(RemoveABudgetPlanWantCommand $removeABudgetPlanWantCommand): void
    {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get((string) $removeABudgetPlanWantCommand->getBudgetPlanId()),
            $this->eventClassMap,
        );
        $aggregate->removeAWant(
            $removeABudgetPlanWantCommand->getEntryId(),
            $removeABudgetPlanWantCommand->getBudgetPlanUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
