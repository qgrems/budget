<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\CreditABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class CreditABudgetEnvelopeCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(CreditABudgetEnvelopeCommand $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        $aggregate->credit(
            $command->getBudgetEnvelopeCreditMoney(),
            $command->getBudgetEnvelopeEntryDescription(),
            $command->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate);
    }
}
