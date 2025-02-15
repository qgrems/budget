<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DeleteABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\DeleteABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\SharedContext\Domain\Services\EventClassMap;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private DeleteABudgetEnvelopeCommandHandler $deleteABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private EventClassMap $eventClassMap;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventClassMap = new EventClassMap();
        $this->deleteABudgetEnvelopeCommandHandler = new DeleteABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->eventClassMap,
        );
    }

    public function testDeleteABudgetEnvelopeSuccess(): void
    {
        $deleteABudgetEnvelopeCommand = new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'event_name' => BudgetEnvelopeAddedDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'name' => 'test1',
                            'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'targetedAmount' => '20.00',
                        ]),
                    ],
                ],
            ),
        );

        $this->eventStore->expects($this->once())->method('save');

        $this->deleteABudgetEnvelopeCommandHandler->__invoke($deleteABudgetEnvelopeCommand);
    }

    public function testDeleteABudgetEnvelopeAlreadyDeleted(): void
    {
        $deleteABudgetEnvelopeCommand = new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'event_name' => BudgetEnvelopeAddedDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'name' => 'test1',
                            'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'targetedAmount' => '20.00',
                        ]),
                    ],
                    [
                        'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'event_name' => BudgetEnvelopeDeletedDomainEvent::class,
                        'stream_version' => 1,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'creditMoney' => '5.47',
                            'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c3',
                            'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'isDeleted' => true,
                        ]),
                    ],
                ],
            ),
        );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->deleteABudgetEnvelopeCommandHandler->__invoke($deleteABudgetEnvelopeCommand);
    }

    public function testDeleteABudgetEnvelopeWithWrongUser(): void
    {
        $deleteABudgetEnvelopeCommand = new DeleteABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn(
                CreateEventGenerator::create(
                    [
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeAddedDomainEvent::class,
                            'stream_version' => 0,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test1',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                                'targetedAmount' => '2000.00',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeRenamedDomainEvent::class,
                            'stream_version' => 1,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test2',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c3',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->deleteABudgetEnvelopeCommandHandler->__invoke($deleteABudgetEnvelopeCommand);
    }
}
