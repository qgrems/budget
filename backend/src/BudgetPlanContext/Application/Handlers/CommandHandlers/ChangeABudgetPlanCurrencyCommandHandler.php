<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\ChangeABudgetPlanCurrencyCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class ChangeABudgetPlanCurrencyCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        ChangeABudgetPlanCurrencyCommand $changeABudgetPlanCurrencyCommand
    ): void {
        $aggregate = BudgetPlan::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $changeABudgetPlanCurrencyCommand->getBudgetPlanId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->changeCurrency(
            $changeABudgetPlanCurrencyCommand->getBudgetPlanCurrency(),
            $changeABudgetPlanCurrencyCommand->getBudgetPlanUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
