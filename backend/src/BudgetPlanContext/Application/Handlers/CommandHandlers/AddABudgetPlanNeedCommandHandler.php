<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\AddABudgetPlanNeedCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AddABudgetPlanNeedCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(AddABudgetPlanNeedCommand $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->addNeed(
            $command->getBudgetPlanId(),
            $command->getEntryId(),
            $command->getName(),
            $command->getAmount(),
            $command->getCategory(),
            $command->getUserId(),
        );
        $this->eventSourcedRepository->save($aggregate);
    }
}
