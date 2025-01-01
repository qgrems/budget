<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\DebitABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers\DebitABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreditedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDebitedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeDeletedEvent;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeRenamedEvent;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeCurrentBudgetException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\InvalidBudgetEnvelopeOperationException;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\DebitABudgetEnvelopeInput;
use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebitABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private DebitABudgetEnvelopeCommandHandler $debitABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->debitABudgetEnvelopeCommandHandler = new DebitABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testDebitABudgetEnvelopeSuccess(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('1.00');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            $debitABudgetEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            'a871e446-ddcd-4e7a-9bf9-525bab84e566'
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
                        'targetBudget' => '2000.00',
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

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeNotFoundFailure(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('100');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            $debitABudgetEnvelopeInput->getDebitMoney(),
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'd26cc02e-99e7-428c-9d61-572dff3f84a7'
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException(BudgetEnvelopeNotFoundException::MESSAGE, 404));
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNotFoundException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeExceedsDebitLimit(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('100');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            $debitABudgetEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            'a871e446-ddcd-4e7a-9bf9-525bab84e566'
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
                        'targetBudget' => '2000.00',
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
            ]);

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeCurrentBudgetException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitDeletedEnvelope(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('3000.00');

        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            $debitABudgetEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            'a871e446-ddcd-4e7a-9bf9-525bab84e566'
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
                        'targetBudget' => '2000.00',
                    ]),
                ],
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => BudgetEnvelopeDeletedEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'debitMoney' => '5.47',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'isDeleted' => true,
                    ]),
                ],
            ]);

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(InvalidBudgetEnvelopeOperationException::class);

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }

    public function testDebitABudgetEnvelopeWithWrongUser(): void
    {
        $debitABudgetEnvelopeInput = new DebitABudgetEnvelopeInput('3000.00');
        $debitABudgetEnvelopeCommand = new DebitABudgetEnvelopeCommand(
            $debitABudgetEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            '0d6851a2-5123-40df-939b-8f043850fbf1'
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
                        'targetBudget' => '2000.00',
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

        $this->debitABudgetEnvelopeCommandHandler->__invoke($debitABudgetEnvelopeCommand);
    }
}
