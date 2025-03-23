<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\ChangeABudgetPlanCurrencyCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class ChangeABudgetPlanCurrencyCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeABudgetPlanCurrencyCommand $command): void
    {
        /** @var BudgetPlan $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetPlanId());
        $aggregate->changeCurrency($command->getBudgetPlanCurrency(), $command->getBudgetPlanUserId());
    }
}
