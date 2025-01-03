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
        try {
            $this->eventSourcedRepository->get((string) $createABudgetEnvelopeCommand->getBudgetEnvelopeId());
        } catch (\RuntimeException $exception) {
            $aggregate = BudgetEnvelope::create(
                $createABudgetEnvelopeCommand->getBudgetEnvelopeId(),
                $createABudgetEnvelopeCommand->getBudgetEnvelopeUserId(),
                $createABudgetEnvelopeCommand->getBudgetEnvelopeTargetBudget(),
                $createABudgetEnvelopeCommand->getBudgetEnvelopeName(),
                $this->budgetEnvelopeViewRepository,
            );
            $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
            $aggregate->clearUncommitedEvent();

            return;
        }

        throw new BudgetEnvelopeAlreadyExistsException(BudgetEnvelopeAlreadyExistsException::MESSAGE, 400);
    }
}
