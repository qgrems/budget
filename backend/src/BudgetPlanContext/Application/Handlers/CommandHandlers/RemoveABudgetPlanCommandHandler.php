<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RemoveABudgetPlanCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(RemoveABudgetPlanCommand $removeABudgetPlanCommand): void
    {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get((string) $removeABudgetPlanCommand->getBudgetPlanId()),
            $this->eventClassMap,
        );
        $aggregate->remove($removeABudgetPlanCommand->getBudgetPlanUserId());
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
