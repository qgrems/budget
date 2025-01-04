<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\UpdateABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeManagement\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class UpdateABudgetEnvelopeTargetedAmountCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(UpdateABudgetEnvelopeTargetedAmountCommand $updateABudgetEnvelopeTargetedAmountCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $updateABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeId());
        $aggregate = BudgetEnvelope::fromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->updateTargetedAmount(
            $updateABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeTargetedAmount(),
            $updateABudgetEnvelopeTargetedAmountCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
