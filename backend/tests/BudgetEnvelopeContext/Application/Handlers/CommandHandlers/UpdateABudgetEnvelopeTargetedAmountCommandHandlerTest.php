<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\UpdateABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\UpdateABudgetEnvelopeTargetedAmountCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedDomainEvent;
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
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\UpdateABudgetEnvelopeTargetedAmountInput;
use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateABudgetEnvelopeTargetedAmountCommandHandlerTest extends TestCase
{
    private UpdateABudgetEnvelopeTargetedAmountCommandHandler $updateABudgetEnvelopeTargetedAmountCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler = new UpdateABudgetEnvelopeTargetedAmountCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testupdateABudgetEnvelopeTargetedAmountSuccess(): void
    {
        $updateABudgetEnvelopeTargetedAmountInput = new UpdateABudgetEnvelopeTargetedAmountInput('100.00', '0.00');
        $updateABudgetEnvelopeTargetedAmountCommand = new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $updateABudgetEnvelopeTargetedAmountInput->currentAmount,
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
                            'type' => BudgetEnvelopeCreatedDomainEvent::class,
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
                            'type' => BudgetEnvelopeRenamedDomainEvent::class,
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
                            'type' => BudgetEnvelopeCreditedDomainEvent::class,
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
                            'type' => BudgetEnvelopeDebitedDomainEvent::class,
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

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }

    public function testUpdateABudgetEnvelopeTargetedAmountNotFoundFailure(): void
    {
        $updateABudgetEnvelopeTargetedAmountInput = new UpdateABudgetEnvelopeTargetedAmountInput('100.00', '0.00');
        $updateABudgetEnvelopeTargetedAmountCommand = new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $updateABudgetEnvelopeTargetedAmountInput->currentAmount,
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException());
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNotFoundException::class);

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }

    public function testUpdateABudgetEnvelopeTargetedAmountIsBelowCurrentAmount(): void
    {
        $updateABudgetEnvelopeTargetedAmountInput = new UpdateABudgetEnvelopeTargetedAmountInput('1000.00', '0.00');
        $updateABudgetEnvelopeTargetedAmountCommand = new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $updateABudgetEnvelopeTargetedAmountInput->currentAmount,
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
                            'type' => BudgetEnvelopeCreatedDomainEvent::class,
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
                            'type' => BudgetEnvelopeCreditedDomainEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'creditMoney' => '2000.00',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeTargetedAmountException::class);

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }

    public function testUpdateABudgetEnvelopeTargetedAmountOnDeletedEnvelope(): void
    {
        $updateABudgetEnvelopeTargetedAmountInput = new UpdateABudgetEnvelopeTargetedAmountInput('3000.00', '0.00');
        $updateABudgetEnvelopeTargetedAmountCommand = new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $updateABudgetEnvelopeTargetedAmountInput->currentAmount,
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
                            'type' => BudgetEnvelopeCreatedDomainEvent::class,
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
                            'type' => BudgetEnvelopeDeletedDomainEvent::class,
                            'occurred_on' => '2020-10-10T12:00:00Z',
                            'payload' => json_encode([
                                'targetedAmount' => '5.47',
                                'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                                'occurredOn' => '2024-12-07T22:03:35+00:00',
                                'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                                'isDeleted' => true,
                            ]),
                        ],
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }

    public function testUpdateABudgetEnvelopeTargetedAmountBelowZero(): void
    {
        $this->expectException(BudgetEnvelopeTargetedAmountException::class);

        new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                '-3000.00',
                '0.00',
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );
    }

    public function testUpdateABudgetEnvelopeTargetedAmountWithWrongUser(): void
    {
        $updateABudgetEnvelopeTargetedAmountInput = new UpdateABudgetEnvelopeTargetedAmountInput('3000.00', '0.00');
        $updateABudgetEnvelopeTargetedAmountCommand = new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                $updateABudgetEnvelopeTargetedAmountInput->currentAmount,
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
                            'type' => BudgetEnvelopeCreatedDomainEvent::class,
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
                            'type' => BudgetEnvelopeRenamedDomainEvent::class,
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
        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }
}
