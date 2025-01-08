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
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get(
                (string) $creditABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            ),
        );
        $aggregate->credit(
            $creditABudgetEnvelopeCommand->getBudgetEnvelopeCreditMoney(),
            $creditABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
