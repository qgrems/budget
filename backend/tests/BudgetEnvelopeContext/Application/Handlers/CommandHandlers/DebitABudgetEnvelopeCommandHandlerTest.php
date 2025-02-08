<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\DebitABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\DebitABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeCurrentAmountException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeDebitMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\DebitABudgetEnvelopeInput;
use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Domain\Services\EventClassMap;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebitABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private DebitABudgetEnvelopeCommandHandler $debitABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private EventClassMap $eventClassMap;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventClassMap = new EventClassMap();

        $this->debitABudgetEnvelopeCommandHandler = new DebitABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->eventClassMap,
        );
    }

    public function testDebitABudgetEnvelopeSuccess(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('1.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
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
                            'event_name' => BudgetEnvelopeCreditedDomainEvent::class,
                            'stream_version' => 1,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '5.47',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c3',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeDebitedDomainEvent::class,
                            'stream_version' => 2,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'debitMoney' => '2.46',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c4',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );
        $this->eventStore->expects($this->once())->method('save');

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeNotFoundFailure(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('100', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('0099c0ce-3b53-4318-ba7b-994e437a859b'),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException());
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNotFoundException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeExceedsDebitLimit(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('100', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
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
                            'event_name' => BudgetEnvelopeCreditedDomainEvent::class,
                            'stream_version' => 1,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '5.47',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c3',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeCurrentAmountException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitDeletedEnvelope(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('3000.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
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
                            'event_name' => BudgetEnvelopeDeletedDomainEvent::class,
                            'stream_version' => 1,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'debitMoney' => '5.47',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c3',
                                'isDeleted' => true,
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeWithWrongUser(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('3000.00', 'test');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            BudgetEnvelopeDebitMoney::fromString($debitABudgetEnvelopeInput->debitMoney),
            BudgetEnvelopeEntryDescription::fromString($debitABudgetEnvelopeInput->description),
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
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c4',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }
}
