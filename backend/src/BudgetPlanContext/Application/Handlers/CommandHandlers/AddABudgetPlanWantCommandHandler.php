<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AddABudgetPlanWantCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AddABudgetPlanWantCommand $addABudgetPlanWantCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $addABudgetPlanWantCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->addWant(
            $addABudgetPlanWantCommand->getBudgetPlanId(),
            $addABudgetPlanWantCommand->getEntryId(),
            $addABudgetPlanWantCommand->getName(),
            $addABudgetPlanWantCommand->getAmount(),
            $addABudgetPlanWantCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
