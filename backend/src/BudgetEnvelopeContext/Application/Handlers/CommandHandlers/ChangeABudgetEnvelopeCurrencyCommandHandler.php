<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeCurrencyCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class ChangeABudgetEnvelopeCurrencyCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        ChangeABudgetEnvelopeCurrencyCommand $changeABudgetEnvelopeCurrencyCommand
    ): void {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $changeABudgetEnvelopeCurrencyCommand->getBudgetEnvelopeId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->changeCurrency(
            $changeABudgetEnvelopeCurrencyCommand->getBudgetEnvelopeCurrency(),
            $changeABudgetEnvelopeCurrencyCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
