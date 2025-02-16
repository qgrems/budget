<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\AddABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\AddABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeAddedDomainEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeAlreadyExistsException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeCurrency;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeTargetedAmount;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\Gateway\BudgetEnvelope\Presentation\HTTP\DTOs\AddABudgetEnvelopeInput;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AddABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private AddABudgetEnvelopeCommandHandler $addABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->addABudgetEnvelopeCommandHandler = new AddABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->envelopeViewRepository,
        );
    }

    public function testAddABudgetEnvelopeSuccess(): void
    {
        $addABudgetEnvelopeInput = new AddABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00',
            'USD',
        );
        $addABudgetEnvelopeCommand = new AddABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetedAmount::fromString(
                $addABudgetEnvelopeInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(CreateEventGenerator::create([]));
        $this->eventStore->expects($this->once())->method('save');

        $this->addABudgetEnvelopeCommandHandler->__invoke($addABudgetEnvelopeCommand);
    }

    public function testAddABudgetEnvelopeWithNameDoubloonFailure(): void
    {
        $addABudgetEnvelopeInput = new AddABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00',
            'USD',
        );
        $addABudgetEnvelopeCommand = new AddABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetedAmount::fromString(
                $addABudgetEnvelopeInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
        );

        $envelopeView = BudgetEnvelopeView::fromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'another envelope name',
                'targeted_amount' => '300.00',
                'current_amount' => '150.00',
                'currency' => 'USD',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'is_deleted' => false,
            ]
        );

        $this->envelopeViewRepository->expects($this->once())->method('findOneBy')->willReturn($envelopeView);
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNameAlreadyExistsForUserException::class);
        $this->expectExceptionMessage(BudgetEnvelopeNameAlreadyExistsForUserException::MESSAGE);

        $this->addABudgetEnvelopeCommandHandler->__invoke($addABudgetEnvelopeCommand);
    }

    public function testAddABudgetEnvelopeWithSameUuidFailure(): void
    {
        $addABudgetEnvelopeInput = new AddABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00',
            'USD',
        );
        $addABudgetEnvelopeCommand = new AddABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($addABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($addABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetedAmount::fromString(
                $addABudgetEnvelopeInput->targetedAmount,
                '0.00',
            ),
            BudgetEnvelopeCurrency::fromString($addABudgetEnvelopeInput->currency),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => $addABudgetEnvelopeInput->uuid,
                        'event_name' => BudgetEnvelopeAddedDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'name' => 'test1',
                            'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'aggregateId' => $addABudgetEnvelopeInput->uuid,
                            'targetedAmount' => '20.00',
                            'currency' => 'USD',
                        ]),
                    ],
                ],
            ),
        );
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeAlreadyExistsException::class);
        $this->expectExceptionMessage(BudgetEnvelopeAlreadyExistsException::MESSAGE);

        $this->addABudgetEnvelopeCommandHandler->__invoke($addABudgetEnvelopeCommand);
    }
}
