<?php

declare(strict_types=1);

namespace App\EnvelopeManagement\Application\Handlers\CommandHandlers;

use App\EnvelopeManagement\Application\Commands\CreateEnvelopeCommand;
use App\EnvelopeManagement\Domain\Aggregates\Envelope;
use App\EnvelopeManagement\Domain\Exceptions\EnvelopeAlreadyExistsException;
use App\EnvelopeManagement\Domain\Ports\Inbound\EnvelopeViewRepositoryInterface;
use App\SharedContext\Domain\Ports\Inbound\EventSourcedRepositoryInterface;

final readonly class CreateEnvelopeCommandHandler
{
    public function __construct(
        private EventSourcedRepositoryInterface $eventSourcedRepository,
        private EnvelopeViewRepositoryInterface $envelopeViewRepository,
    ) {
    }

    public function __invoke(CreateEnvelopeCommand $command): void
    {
        try {
            $this->eventSourcedRepository->get($command->getUuid());
        } catch (\RuntimeException $exception) {
            $aggregate = Envelope::create(
                $command->getUuid(),
                $command->getUserUuid(),
                $command->getTargetBudget(),
                $command->getName(),
                $this->envelopeViewRepository,
            );
            $this->eventSourcedRepository->save($aggregate->getUncommittedEvents());
            $aggregate->clearUncommitedEvent();

            return;
        }

        throw new EnvelopeAlreadyExistsException(EnvelopeAlreadyExistsException::MESSAGE, 400);
    }
}
