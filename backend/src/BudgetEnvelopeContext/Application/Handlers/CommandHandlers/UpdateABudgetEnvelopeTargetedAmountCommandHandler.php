<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\UpdateABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class UpdateABudgetEnvelopeTargetedAmountCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(UpdateABudgetEnvelopeTargetedAmountCommand $updateABudgetEnvelopeTargetedAmountCommand): void
    {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $updateABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeId(),
            ),
        );
        $aggregate->updateTargetedAmount(
            $updateABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeTargetedAmount(),
            $updateABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
