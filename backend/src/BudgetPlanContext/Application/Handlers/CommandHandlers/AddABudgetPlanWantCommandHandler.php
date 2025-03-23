<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanWantCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AddABudgetPlanWantCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(AddABudgetPlanWantCommand $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->addWant(
            $command->getBudgetPlanId(),
            $command->getEntryId(),
            $command->getName(),
            $command->getAmount(),
            $command->getCategory(),
            $command->getUserId(),
        );
    }
}
