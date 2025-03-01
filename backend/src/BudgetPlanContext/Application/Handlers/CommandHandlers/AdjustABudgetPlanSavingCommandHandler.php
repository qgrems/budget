<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AdjustABudgetPlanSavingCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AdjustABudgetPlanSavingCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AdjustABudgetPlanSavingCommand $adjustABudgetPlanSavingCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $adjustABudgetPlanSavingCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->adjustASaving(
            $adjustABudgetPlanSavingCommand->getBudgetPlanId(),
            $adjustABudgetPlanSavingCommand->getEntryId(),
            $adjustABudgetPlanSavingCommand->getName(),
            $adjustABudgetPlanSavingCommand->getAmount(),
            $adjustABudgetPlanSavingCommand->getCategory(),
            $adjustABudgetPlanSavingCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
