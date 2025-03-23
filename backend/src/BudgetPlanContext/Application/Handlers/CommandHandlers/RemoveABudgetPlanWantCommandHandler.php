<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\RemoveABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RemoveABudgetPlanWantCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(RemoveABudgetPlanWantCommand $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->removeAWant($command->getEntryId(), $command->getBudgetPlanUserId());
    }
}
