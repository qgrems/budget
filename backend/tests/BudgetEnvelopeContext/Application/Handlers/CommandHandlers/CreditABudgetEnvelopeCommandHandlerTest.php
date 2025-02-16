<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\CreditABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\CreditABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreditedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDebitedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeDeletedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeRenamedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeCurrentAmountException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeEntryDescription;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\CreditABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\SharedContext\Domain\Services\EventClassMap;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private CreditABudgetEnvelopeCommandHandler $creditABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private EventClassMap $eventClassMap;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventClassMap = new EventClassMap();

        $this->creditABudgetEnvelopeCommandHandler = new CreditABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->eventClassMap,
        );
    }

    public function testCreditABudgetEnvelopeSuccess(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
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
                                'currency' => 'USD',
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
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeCreditedDomainEvent::class,
                            'stream_version' => 2,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '5.47',
                                'description' => 'test',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c4',
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
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c5',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );
        $this->eventStore->expects($this->once())->method('save');

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeNotFoundFailure(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException());
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNotFoundException::class);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeExceedsCreditLimit(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('3000.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
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
                                'currency' => 'USD',
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
        $this->expectException(BudgetEnvelopeCurrentAmountException::class);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditDeletedEnvelope(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('3000.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
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
                                'currency' => 'USD',
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
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'event_name' => BudgetEnvelopeDeletedDomainEvent::class,
                            'stream_version' => 2,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '5.47',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c4',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'isDeleted' => true,
                            ]),
                        ],
                    ]
                )
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeWithWrongUser(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('3000.00', 'test');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeEntryDescription::fromString($creditABudgetEnvelopeInput->description),
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
                                'currency' => 'USD',
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

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }
}
