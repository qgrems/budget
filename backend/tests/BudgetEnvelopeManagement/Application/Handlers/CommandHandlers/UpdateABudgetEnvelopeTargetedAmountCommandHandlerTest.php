<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\UpdateABudgetEnvelopeTargetedAmountCommand;
use App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers\UpdateABudgetEnvelopeTargetedAmountCommandHandler;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeTargetedAmountException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\UpdateABudgetEnvelopeTargetedAmountInput;
use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
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
                '0.00',
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn([
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
            ]);
        $this->eventStore->expects($this->once())->method('save');

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }

    public function testUpdateABudgetEnvelopeTargetedAmountNotFoundFailure(): void
    {
        $updateABudgetEnvelopeTargetedAmountInput = new UpdateABudgetEnvelopeTargetedAmountInput('100.00', '0.00');
        $updateABudgetEnvelopeTargetedAmountCommand = new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException(BudgetEnvelopeNotFoundException::MESSAGE, 404));
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
            ->willReturn([
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
                    'type' => BudgetEnvelopeCreditedEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'creditMoney' => '2000.00',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    ]),
                ],
            ]);

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
                '0.00',
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('a871e446-ddcd-4e7a-9bf9-525bab84e566'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn([
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
                    'type' => BudgetEnvelopeDeletedEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'targetedAmount' => '5.47',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'isDeleted' => true,
                    ]),
                ],
            ]);

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }

    public function testUpdateABudgetEnvelopeTargetedAmountWithWrongUser(): void
    {
        $updateABudgetEnvelopeTargetedAmountInput = new UpdateABudgetEnvelopeTargetedAmountInput('3000.00', '0.00');
        $updateABudgetEnvelopeTargetedAmountCommand = new UpdateABudgetEnvelopeTargetedAmountCommand(
            BudgetEnvelopeTargetedAmount::fromString(
                $updateABudgetEnvelopeTargetedAmountInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'),
            BudgetEnvelopeUserId::fromString('0d6851a2-5123-40df-939b-8f043850fbf1'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn([
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
            ]);

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(\RuntimeException::class);

        $this->updateABudgetEnvelopeTargetedAmountCommandHandler->__invoke($updateABudgetEnvelopeTargetedAmountCommand);
    }
}
