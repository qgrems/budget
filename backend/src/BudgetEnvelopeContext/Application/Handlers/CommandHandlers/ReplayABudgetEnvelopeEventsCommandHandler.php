<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ReplayABudgetEnvelopeEventsCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class ReplayABudgetEnvelopeEventsCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(ReplayABudgetEnvelopeEventsCommand $replayABudgetEnvelopeCommand): void
    {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $replayABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            ),
        );
        $aggregate->replay(
            $replayABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
