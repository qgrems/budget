<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanIncomeCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RemoveABudgetPlanIncomeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(RemoveABudgetPlanIncomeCommand $removeABudgetPlanIncomeCommand): void
    {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get((string) $removeABudgetPlanIncomeCommand->getBudgetPlanId()),
            $this->eventClassMap,
        );
        $aggregate->removeAnIncome(
            $removeABudgetPlanIncomeCommand->getEntryId(),
            $removeABudgetPlanIncomeCommand->getBudgetPlanUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
