<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanSavingCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RemoveABudgetPlanSavingCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(RemoveABudgetPlanSavingCommand $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->removeASaving($command->getEntryId(), $command->getBudgetPlanUserId());
        $this->eventSourcedRepository->save($aggregate);
    }
}
