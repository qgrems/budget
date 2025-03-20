<?php

declare(strict_types=1);

namespace App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DeleteABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Domain\Aggregates\BudgetEnvelope;
use App\BudgetEnvelopeContext\Domain\Builders\BudgetEnvelopeNameRegistryBuilder;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeNameRegistryId;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;

final readonly class DeleteABudgetEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function __invoke(DeleteABudgetEnvelopeCommand $command): void
    {
        /** @var BudgetEnvelope $aggregate */
        $aggregate = $this->eventSourcedRepository->get((string) $command->getBudgetEnvelopeId());
        $aggregate->delete($command->getBudgetEnvelopeUserId());
        $aggregatesToSave = BudgetEnvelopeNameRegistryBuilder::build(
            $this->eventSourcedRepository,
            $this->uuidGenerator,
        )
            ->loadOldRegistry(
                BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
                    $command->getBudgetEnvelopeUserId(),
                    $aggregate->getBudgetEnvelopeName(),
                    $this->uuidGenerator,
                ),
            )
            ->releaseName($aggregate->getBudgetEnvelopeName(), $command->getBudgetEnvelopeUserId())
            ->getRegistryAggregates();
        $aggregatesToSave[] = $aggregate;
        $this->eventSourcedRepository->saveMultiAggregate($aggregatesToSave);
        $this->eventSourcedRepository->save($aggregate);
    }
}
