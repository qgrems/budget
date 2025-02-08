<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DebitABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class DebitABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(DebitABudgetEnvelopeCommand $debitABudgetEnvelopeCommand): void
    {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get((string) $debitABudgetEnvelopeCommand->getBudgetEnvelopeId()),
            $this->eventClassMap,
        );
        $aggregate->debit(
            $debitABudgetEnvelopeCommand->getBudgetEnvelopeDebitMoney(),
            $debitABudgetEnvelopeCommand->getBudgetEnvelopeEntryDescription(),
            $debitABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
