<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\CreateABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeAlreadyExistsException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
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
            throw new BudgetEnvelopeAlreadyExistsException();
        }

        $aggregate = BudgetEnvelope::create(
            $createABudgetEnvelopeCommand->getBudgetEnvelopeId(),
            $createABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
            $createABudgetEnvelopeCommand->getBudgetEnvelopeTargetedAmount(),
            $createABudgetEnvelopeCommand->getBudgetEnvelopeName(),
            $this->budgetEnvelopeViewRepository,
        );
        $this->eventSourcedRepository->save($aggregate->raisedDomainEvents());
        $aggregate->clearRaisedDomainEvents();
    }
}
