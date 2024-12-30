<?php

declare(strict_types=1);

namespace App\Tests\EnvelopeManagement\Application\Handlers\CommandHandlers;

use App\EnvelopeManagement\Application\Commands\DebitEnvelopeCommand;
use App\EnvelopeManagement\Application\Handlers\CommandHandlers\DebitEnvelopeCommandHandler;
use App\EnvelopeManagement\Domain\Events\EnvelopeCreatedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeCreditedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeDebitedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeDeletedEvent;
use App\EnvelopeManagement\Domain\Events\EnvelopeNamedEvent;
use App\EnvelopeManagement\Domain\Exceptions\CurrentBudgetException;
use App\EnvelopeManagement\Domain\Exceptions\EnvelopeNotFoundException;
use App\EnvelopeManagement\Domain\Exceptions\InvalidEnvelopeOperationException;
use App\EnvelopeManagement\Presentation\HTTP\DTOs\DebitEnvelopeInput;
use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DebitEnvelopeCommandHandlerTest extends TestCase
{
    private DebitEnvelopeCommandHandler $debitEnvelopeCommandHandler;

    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->debitEnvelopeCommandHandler = new DebitEnvelopeCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testDebitEnvelopeSuccess(): void
    {
        $debitEnvelopeInput = new DebitEnvelopeInput('1.00');
        $debitEnvelopeCommand = new DebitEnvelopeCommand(
            $debitEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            'a871e446-ddcd-4e7a-9bf9-525bab84e566'
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn([
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeCreatedEvent::class,
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
                    'type' => EnvelopeCreditedEvent::class,
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
                    'type' => EnvelopeDebitedEvent::class,
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

        $this->debitEnvelopeCommandHandler->__invoke($debitEnvelopeCommand);
    }

    public function testDebitEnvelopeNotFoundFailure(): void
    {
        $debitEnvelopeInput = new DebitEnvelopeInput('100');
        $debitEnvelopeCommand = new DebitEnvelopeCommand(
            $debitEnvelopeInput->getDebitMoney(),
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'd26cc02e-99e7-428c-9d61-572dff3f84a7'
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new EnvelopeNotFoundException(EnvelopeNotFoundException::MESSAGE, 404));
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(EnvelopeNotFoundException::class);

        $this->debitEnvelopeCommandHandler->__invoke($debitEnvelopeCommand);
    }

    public function testDebitEnvelopeExceedsDebitLimit(): void
    {
        $debitEnvelopeInput = new DebitEnvelopeInput('100');
        $debitEnvelopeCommand = new DebitEnvelopeCommand(
            $debitEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            'a871e446-ddcd-4e7a-9bf9-525bab84e566'
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn([
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeCreatedEvent::class,
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
                    'type' => EnvelopeCreditedEvent::class,
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
        $this->expectException(CurrentBudgetException::class);

        $this->debitEnvelopeCommandHandler->__invoke($debitEnvelopeCommand);
    }

    public function testDebitDeletedEnvelope(): void
    {
        $debitEnvelopeInput = new DebitEnvelopeInput('3000.00');

        $debitEnvelopeCommand = new DebitEnvelopeCommand(
            $debitEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            'a871e446-ddcd-4e7a-9bf9-525bab84e566'
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn([
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeCreatedEvent::class,
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
                    'type' => EnvelopeDeletedEvent::class,
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
        $this->expectException(InvalidEnvelopeOperationException::class);

        $this->debitEnvelopeCommandHandler->__invoke($debitEnvelopeCommand);
    }

    public function testDebitEnvelopeWithWrongUser(): void
    {
        $debitEnvelopeInput = new DebitEnvelopeInput('3000.00');
        $debitEnvelopeCommand = new DebitEnvelopeCommand(
            $debitEnvelopeInput->getDebitMoney(),
            '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
            '0d6851a2-5123-40df-939b-8f043850fbf1'
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willReturn([
                [
                    'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                    'type' => EnvelopeCreatedEvent::class,
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
                    'type' => EnvelopeNamedEvent::class,
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

        $this->debitEnvelopeCommandHandler->__invoke($debitEnvelopeCommand);
    }
}
