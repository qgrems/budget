<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanNeedCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RemoveABudgetPlanNeedCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(RemoveABudgetPlanNeedCommand $removeABudgetPlanNeedCommand): void
    {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get((string) $removeABudgetPlanNeedCommand->getBudgetPlanId()),
            $this->eventClassMap,
        );
        $aggregate->removeANeed(
            $removeABudgetPlanNeedCommand->getEntryId(),
            $removeABudgetPlanNeedCommand->getBudgetPlanUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
