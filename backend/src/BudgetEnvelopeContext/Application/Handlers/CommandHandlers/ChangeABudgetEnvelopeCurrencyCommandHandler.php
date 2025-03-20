<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeCurrencyCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class ChangeABudgetEnvelopeCurrencyCommandHandler
{
    public function __construct(private EventSourcedRepositoryInterface $eventSourcedRepository)
    {
    }

    public function __invoke(ChangeABudgetEnvelopeCurrencyCommand $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get(
            (string) $command->getBudgetEnvelopeId(),
        );
        $aggregate->changeCurrency(
            $command->getBudgetEnvelopeCurrency(),
            $command->getBudgetEnvelopeUserId(),
        );
        $this->eventSourcedRepository->save($aggregate);
    }
}
