<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AdjustABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AdjustABudgetPlanWantCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AdjustABudgetPlanWantCommand $adjustABudgetPlanWantCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $adjustABudgetPlanWantCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->adjustAWant(
            $adjustABudgetPlanWantCommand->getBudgetPlanId(),
            $adjustABudgetPlanWantCommand->getEntryId(),
            $adjustABudgetPlanWantCommand->getName(),
            $adjustABudgetPlanWantCommand->getAmount(),
            $adjustABudgetPlanWantCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
