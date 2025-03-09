<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DeleteABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class DeleteABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EventClassMapInterface $eventClassMap,
    ) {
    }

    public function __invoke(DeleteABudgetEnvelopeCommand $deleteABudgetEnvelopeCommand): void
    {
        $aggregate = BudgetEnvelope::fromEvents(
            $this->eventSourcedRepository->get((string) $deleteABudgetEnvelopeCommand->getBudgetEnvelopeId()),
            $this->eventClassMap,
        );
        $aggregate->delete($deleteABudgetEnvelopeCommand->getBudgetEnvelopeUserId());
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), $aggregate->aggregateVersion());
        $aggregate->clearRaisedDomainEvents();
    }
}
