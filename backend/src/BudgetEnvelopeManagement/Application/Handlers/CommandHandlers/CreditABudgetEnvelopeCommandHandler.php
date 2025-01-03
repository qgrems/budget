<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\CreditABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class CreditABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
    ) {
    }

    public function __invoke(CreditABudgetEnvelopeCommand $creditABudgetEnvelopeCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $creditABudgetEnvelopeCommand->getBudgetEnvelopeId());
        $aggregate = BudgetEnvelope::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->credit(
            $creditABudgetEnvelopeCommand->getBudgetEnvelopeCreditMoney(),
            $creditABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
