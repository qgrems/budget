<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\CreditABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers\CreditABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeCurrentAmountException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeCreditMoney;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\CreditABudgetEnvelopeInput;
use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreditABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private CreditABudgetEnvelopeCommandHandler $creditABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->creditABudgetEnvelopeCommandHandler = new CreditABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testCreditABudgetEnvelopeSuccess(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn(
                CreateEventGenerator::create(
                    [
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeCreatedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test1',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'targetedAmount' => '2000.00',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeRenamedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test2',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeCreditedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '5.47',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeDebitedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'debitMoney' => '2.46',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
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
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('100.00');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException(BudgetEnvelopeNotFoundException::MESSAGE, 404));
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNotFoundException::class);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }

    public function testCreditABudgetEnvelopeExceedsCreditLimit(): void
    {
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('3000.00');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn(
                CreateEventGenerator::create(
                    [
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeCreatedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test1',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'targetedAmount' => '2000.00',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeRenamedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test2',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
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
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('3000.00');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn(
                CreateEventGenerator::create(
                    [
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeCreatedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test1',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'targetedAmount' => '2000.00',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeRenamedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test2',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeDeletedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '5.47',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
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
        $creditABudgetEnvelopeInput = new CreditABudgetEnvelopeInput('3000.00');
        $creditABudgetEnvelopeCommand = new CreditABudgetEnvelopeCommand(
            BudgetEnvelopeCreditMoney::fromString($creditABudgetEnvelopeInput->creditMoney),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn(
                CreateEventGenerator::create(
                    [
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeCreatedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test1',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'targetedAmount' => '2000.00',
                            ]),
                        ],
                        [
                            'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'type' => BudgetEnvelopeRenamedEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'name' => 'test2',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(\RuntimeException::class);

        $this->creditABudgetEnvelopeCommandHandler->__invoke($creditABudgetEnvelopeCommand);
    }
}
