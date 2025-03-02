<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AdjustABudgetPlanNeedCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AdjustABudgetPlanNeedCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AdjustABudgetPlanNeedCommand $adjustABudgetPlanNeedCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $adjustABudgetPlanNeedCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->adjustANeed(
            $adjustABudgetPlanNeedCommand->getBudgetPlanId(),
            $adjustABudgetPlanNeedCommand->getEntryId(),
            $adjustABudgetPlanNeedCommand->getName(),
            $adjustABudgetPlanNeedCommand->getAmount(),
            $adjustABudgetPlanNeedCommand->getCategory(),
            $adjustABudgetPlanNeedCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
