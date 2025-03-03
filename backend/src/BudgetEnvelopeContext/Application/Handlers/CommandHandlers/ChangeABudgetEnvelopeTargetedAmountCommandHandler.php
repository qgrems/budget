<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class ChangeABudgetEnvelopeTargetedAmountCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(
        ChangeABudgetEnvelopeTargetedAmountCommand $changeABudgetEnvelopeTargetedAmountCommand
    ): void {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $changeABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeId(),
            ),
            $this->eventClassMap,
        );
        $aggregate->updateTargetedAmount(
            $changeABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeTargetedAmount(),
            $changeABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
