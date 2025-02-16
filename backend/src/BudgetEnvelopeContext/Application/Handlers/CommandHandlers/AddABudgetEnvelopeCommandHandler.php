<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeAlreadyExistsException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class AddABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private BudgetEnvelopeViewRepositoryInterface $budgetEnvelopeViewRepository,
    ) {
    }

    public function __invoke(AddABudgetEnvelopeCommand $addABudgetEnvelopeCommand): void
    {
        $events = $this->eventSourcedRepository->get((string) $addABudgetEnvelopeCommand->getBudgetEnvelopeId());

        if ($events->current()) {
            throw new BudgetEnvelopeAlreadyExistsException();
        }

        $aggregate = BudgetEnvelope::create(
            $addABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            $addABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
            $addABudgetEnvelopeCommand->getBudgetEnvelopeTargetedAmount(),
            $addABudgetEnvelopeCommand->getBudgetEnvelopeName(),
            $addABudgetEnvelopeCommand->getBudgetEnvelopeCurrency(),
            $this->budgetEnvelopeViewRepository,
        );

        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents(), 0);
        $aggregate->clearRaisedDomainEvents();
    }
}
