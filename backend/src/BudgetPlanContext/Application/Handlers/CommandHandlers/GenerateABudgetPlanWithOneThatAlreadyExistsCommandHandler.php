<?php

declare(strict_types=1);

namespace App\BudgetPlanContext\Application\Handlers\CommandHandlers;

use App\BudgetPlanContext\Application\Commands\GenerateABudgetPlanWithOneThatAlreadyExistsCommand;
use App\BudgetPlanContext\Domain\Aggregates\BudgetPlan;
use App\BudgetPlanContext\Domain\Exceptions\BudgetPlanAlreadyExistsException;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\UuidGeneratorInterface;

final readonly class GenerateABudgetPlanWithOneThatAlreadyExistsCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private UuidGeneratorInterface $uuidGenerator,
    ) {
    }

    public function __invoke(GenerateABudgetPlanWithOneThatAlreadyExistsCommand $command): void
    {
        try {
            $aggregate = $this->eventSourcedRepository->get(
                (string) $command->getBudgetPlanId(),
            );

            if ($aggregate instanceof BudgetPlan) {
                throw new BudgetPlanAlreadyExistsException();
            }

        } catch (EventsNotFoundForAggregateException) {
            /** @var BudgetPlan $aggregateToCopy */
            $aggregateToCopy = $this->eventSourcedRepository->get(
                (string) $command->getBudgetPlanIdThatAlreadyExists(),
            );
            $aggregate = BudgetPlan::createWithOneThatAlreadyExists(
                $command->getBudgetPlanId(),
                $command->getDate(),
                $command->getUserId(),
                $aggregateToCopy,
                $this->uuidGenerator,
            );
            $this->eventSourcedRepository->save($aggregate);
        }
    }
}
