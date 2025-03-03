<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanSavingCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AddABudgetPlanSavingCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AddABudgetPlanSavingCommand $addABudgetPlanSavingCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $addABudgetPlanSavingCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->addSaving(
            $addABudgetPlanSavingCommand->getBudgetPlanId(),
            $addABudgetPlanSavingCommand->getEntryId(),
            $addABudgetPlanSavingCommand->getName(),
            $addABudgetPlanSavingCommand->getAmount(),
            $addABudgetPlanSavingCommand->getCategory(),
            $addABudgetPlanSavingCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
