<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ReplayABudgetEnvelopeEventsCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\ReplayABudgetEnvelopeEventsCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReplayABudgetEnvelopeEventsCommandHandlerTest extends TestCase
{
    private ReplayABudgetEnvelopeEventsCommandHandler $replayABudgetEnvelopeEventsCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->replayABudgetEnvelopeEventsCommandHandler = new ReplayABudgetEnvelopeEventsCommandHandler(
            $this->eventSourcedRepository
        );
    }

    public function testReplayEventsSuccess(): void
    {
        $replayABudgetEnvelopeEventsCommand = new ReplayABudgetEnvelopeEventsCommand(
            BudgetEnvelopeId::fromString('3e6a6763-4c4d-4648-bc3f-e9447dbed12c'),
            BudgetEnvelopeUserId::fromString('18e04f53-0ea6-478c-a02b-81b7f3d6e8c1')
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(CreateEventGenerator::create(
            [
                [
                    'aggregate_id' => '3e6a6763-4c4d-4648-bc3f-e9447dbed12c',
                    'type' => BudgetEnvelopeCreatedDomainEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'name' => 'test1',
                        'userId' => '18e04f53-0ea6-478c-a02b-81b7f3d6e8c1',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '3e6a6763-4c4d-4648-bc3f-e9447dbed12c',
                        'targetedAmount' => '2000.00',
                    ]),
                ],
                [
                    'aggregate_id' => '3e6a6763-4c4d-4648-bc3f-e9447dbed12c',
                    'type' => BudgetEnvelopeCreditedDomainEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'creditMoney' => '5.47',
                        'userId' => '18e04f53-0ea6-478c-a02b-81b7f3d6e8c1',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '3e6a6763-4c4d-4648-bc3f-e9447dbed12c',
                    ]),
                ],
            ],
        ));
        $this->eventStore->expects($this->once())->method('save');

        $this->replayABudgetEnvelopeEventsCommandHandler->__invoke($replayABudgetEnvelopeEventsCommand);
    }

    public function testReplayEventsFailure(): void
    {
        $replayABudgetEnvelopeEventsCommand = new ReplayABudgetEnvelopeEventsCommand(
            BudgetEnvelopeId::fromString('3e6a6763-4c4d-4648-bc3f-e9447dbed12c'),
            BudgetEnvelopeUserId::fromString('18e04f53-0ea6-478c-a02b-81b7f3d6e8c1'),
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \Exception('Error loading events'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error loading events');

        $this->replayABudgetEnvelopeEventsCommandHandler->__invoke($replayABudgetEnvelopeEventsCommand);
    }
}
