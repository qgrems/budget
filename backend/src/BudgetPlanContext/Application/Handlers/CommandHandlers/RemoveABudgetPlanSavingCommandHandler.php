<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanSavingCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RemoveABudgetPlanSavingCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(RemoveABudgetPlanSavingCommand $removeABudgetPlanSavingCommand): void
    {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get((string) $removeABudgetPlanSavingCommand->getBudgetPlanId()),
            $this->eventClassMap,
        );
        $aggregate->removeASaving(
            $removeABudgetPlanSavingCommand->getEntryId(),
            $removeABudgetPlanSavingCommand->getBudgetPlanUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
