<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\CreateABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeAlreadyExistsException;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class CreateABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    public function __invoke(CreateABudgetEnvelopeCommand $createABudgetEnvelopeCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $createABudgetEnvelopeCommand->getBudgetEnvelopeId());

        if ($events->current()) {
            throw new BudgetEnvelopeAlreadyExistsException(BudgetEnvelopeAlreadyExistsException::MESSAGE, 400);
        }

        $aggregate = BudgetEnvelope::create(
            $createABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            $createABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
            $createABudgetEnvelopeCommand->getBudgetEnvelopeTargetedAmount(),
            $createABudgetEnvelopeCommand->getBudgetEnvelopeName(),
            $this->budgetEnvelopeViewRepository,
        );
        $this->eventSourcedRepository->save($aggregate->raisedEvents());
        $aggregate->clearRaisedEvents();
    }
}
