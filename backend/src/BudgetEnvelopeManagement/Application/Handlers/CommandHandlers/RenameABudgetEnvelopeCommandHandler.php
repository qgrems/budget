<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\RenameABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class RenameABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    public function __invoke(RenameABudgetEnvelopeCommand $renameABudgetEnvelopeCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $renameABudgetEnvelopeCommand->getBudgetEnvelopeId());
        $aggregate = BudgetEnvelope::reconstituteFromEvents(array_map(fn ($event) => $event, $events));
        $aggregate->rename(
            $renameABudgetEnvelopeCommand->getBudgetEnvelopeName(),
            $renameABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
            $renameABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            $this->budgetEnvelopeViewRepository,
        );
        $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
        $aggregate->clearUncommitedEvent();
    }
}
