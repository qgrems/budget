<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AdjustABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AdjustABudgetPlanWantCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(AdjustABudgetPlanWantCommand $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->adjustAWant(
            $command->getBudgetPlanId(),
            $command->getEntryId(),
            $command->getName(),
            $command->getAmount(),
            $command->getCategory(),
            $command->getUserId(),
        );
    }
}
