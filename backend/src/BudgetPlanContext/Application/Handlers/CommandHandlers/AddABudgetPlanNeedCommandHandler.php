<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanNeedCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AddABudgetPlanNeedCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        AddABudgetPlanNeedCommand $addABudgetPlanNeedCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $addABudgetPlanNeedCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->addNeed(
            $addABudgetPlanNeedCommand->getBudgetPlanId(),
            $addABudgetPlanNeedCommand->getEntryId(),
            $addABudgetPlanNeedCommand->getName(),
            $addABudgetPlanNeedCommand->getAmount(),
            $addABudgetPlanNeedCommand->getCategory(),
            $addABudgetPlanNeedCommand->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
