<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\ChangeABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\ChangeABudgetEnvelopeTargetedAmountCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeTargetedAmountException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\Gateway\BudgetEnvelope\HTTP\DTOs\ChangeABudgetEnvelopeTargetedAmountInput;
use App\SharedContext\Domain\Services\EventClassMap;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeABudgetEnvelopeTargetedAmountCommandHandlerTest extends TestCase
{
    private ChangeABudgetEnvelopeTargetedAmountCommandHandler $changeABudgetEnvelopeTargetedAmountCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventClassMap $eventClassMap;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventClassMap = new EventClassMap();

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler = new ChangeABudgetEnvelopeTargetedAmountCommandHandler(
            $this->eventSourcedRepository,
            $this->eventClassMap,
        );
    }

    public function testChangeABudgetEnvelopeTargetedAmountSuccess(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('100.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
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
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
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
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e567',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeCreditedDomainEvent::class,
                            'stream_version' => 2,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '5.47',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e568',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeDebitedDomainEvent::class,
                            'stream_version' => 3,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'debitMoney' => '2.46',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e569',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );
        $this->eventStore->expects($this->once())->method('save');

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountNotFoundFailure(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('100.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException());
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNotFoundException::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountIsBelowCurrentAmount(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('1000.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
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
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e568',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'targetedAmount' => '2000.00',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeCreditedDomainEvent::class,
                            'stream_version' => 1,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '2000.00',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e567',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeTargetedAmountException::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountOnDeletedEnvelope(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('3000.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
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
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e567',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'targetedAmount' => '2000.00',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeDeletedDomainEvent::class,
                            'stream_version' => 1,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'targetedAmount' => '5.47',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e568',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'isDeleted' => true,
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }

    public function testChangeABudgetEnvelopeTargetedAmountBelowZero(): void
    {
        $this->expectException(BudgetEnvelopeTargetedAmountException::class);

        new ChangeABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                '-3000.00',
                '0.00',
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );
    }

    public function testChangeABudgetEnvelopeTargetedAmountWithWrongUser(): void
    {
        $changeABudgetEnvelopeTargetedAmountInput = new ChangeABudgetEnvelopeTargetedAmountInput('3000.00', '0.00');
        $changeABudgetEnvelopeTargetedAmountCommand = new ChangeABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $changeABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $changeABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
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
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e567',
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
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'requestId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e568',
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->changeABudgetEnvelopeTargetedAmountCommandHandler->__invoke($changeABudgetEnvelopeTargetedAmountCommand);
    }
}
