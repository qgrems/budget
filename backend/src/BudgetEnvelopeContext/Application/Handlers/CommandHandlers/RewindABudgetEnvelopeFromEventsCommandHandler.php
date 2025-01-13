<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\RewindABudgetEnvelopeFromEventsCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RewindABudgetEnvelopeFromEventsCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(RewindABudgetEnvelopeFromEventsCommand $rewindABudgetEnvelopeCommand): void
    {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $rewindABudgetEnvelopeCommand->getBudgetEnvelopeId(),
                $rewindABudgetEnvelopeCommand->getDesiredDateTime(),
            ),
        );
        $aggregate->rewind(
            $rewindABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
