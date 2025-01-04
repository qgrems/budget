<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\DebitABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class DebitABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(DebitABudgetEnvelopeCommand $debitABudgetEnvelopeCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $debitABudgetEnvelopeCommand->getBudgetEnvelopeId());
        $aggregate = BudgetEnvelope::fromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->debit(
            $debitABudgetEnvelopeCommand->getBudgetEnvelopeDebitMoney(),
            $debitABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
