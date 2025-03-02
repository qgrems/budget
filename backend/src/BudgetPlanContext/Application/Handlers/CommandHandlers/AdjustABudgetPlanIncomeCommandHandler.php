<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AdjustABudgetPlanIncomeCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AdjustABudgetPlanIncomeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AdjustABudgetPlanIncomeCommand $adjustABudgetPlanIncomeCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $adjustABudgetPlanIncomeCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->adjustAnIncome(
            $adjustABudgetPlanIncomeCommand->getBudgetPlanId(),
            $adjustABudgetPlanIncomeCommand->getEntryId(),
            $adjustABudgetPlanIncomeCommand->getName(),
            $adjustABudgetPlanIncomeCommand->getAmount(),
            $adjustABudgetPlanIncomeCommand->getCategory(),
            $adjustABudgetPlanIncomeCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
