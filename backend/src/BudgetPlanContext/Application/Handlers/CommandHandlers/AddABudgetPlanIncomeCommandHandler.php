<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanIncomeCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AddABudgetPlanIncomeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AddABudgetPlanIncomeCommand $addABudgetPlanIncomeCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $addABudgetPlanIncomeCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->addIncome(
            $addABudgetPlanIncomeCommand->getBudgetPlanId(),
            $addABudgetPlanIncomeCommand->getEntryId(),
            $addABudgetPlanIncomeCommand->getName(),
            $addABudgetPlanIncomeCommand->getAmount(),
            $addABudgetPlanIncomeCommand->getCategory(),
            $addABudgetPlanIncomeCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
