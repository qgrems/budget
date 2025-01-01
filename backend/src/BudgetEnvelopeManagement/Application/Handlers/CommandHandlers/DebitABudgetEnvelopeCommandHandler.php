<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\DebitABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class DebitABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(DebitABudgetEnvelopeCommand $debitABudgetEnvelopeCommand): void
    {
        $events = $this->eventSourcedRepository->get($debitABudgetEnvelopeCommand->getUuid());
        $aggregate = BudgetEnvelope::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->debit(
            BudgetEnvelopeDebitMoney::create(
                $debitABudgetEnvelopeCommand->getDebitMoney(),
            ),
            BudgetEnvelopeUserId::create($debitABudgetEnvelopeCommand->getUserUuid()),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
