<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeManagement\Application\Commands\CreateABudgetEnvelopeCommand;
use App\BudgetEnvelopeManagement\Application\Handlers\CommandHandlers\CreateABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeManagement\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeAlreadyExistsException;
use App\BudgetEnvelopeManagement\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeManagement\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeTargetBudget;
use App\BudgetEnvelopeManagement\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeManagement\Presentation\HTTP\DTOs\CreateABudgetEnvelopeInput;
use App\BudgetEnvelopeManagement\ReadModels\Views\BudgetEnvelopeView;
use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private CreateABudgetEnvelopeCommandHandler $createABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private BudgetEnvelopeViewRepositoryInterface&MockObject $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);

        $this->createABudgetEnvelopeCommandHandler = new CreateABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->envelopeViewRepository,
        );
    }

    public function testCreateEnvelopeSuccess(): void
    {
        $createABudgetEnvelopeInput = new CreateABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00'
        );
        $createABudgetEnvelopeCommand = new CreateABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($createABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($createABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetBudget::fromString($createABudgetEnvelopeInput->targetBudget),
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \RuntimeException());
        $this->eventStore->expects($this->once())->method('save');

        $this->createABudgetEnvelopeCommandHandler->__invoke($createABudgetEnvelopeCommand);
    }

    public function testCreateEnvelopeWithNameDoubloonFailure(): void
    {
        $createABudgetEnvelopeInput = new CreateABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00'
        );
        $createABudgetEnvelopeCommand = new CreateABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($createABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($createABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetBudget::fromString($createABudgetEnvelopeInput->targetBudget),
        );

        $envelopeView = BudgetEnvelopeView::fromRepository(
            [
                'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                'name' => 'another envelope name',
                'target_budget' => '300.00',
                'current_budget' => '150.00',
                'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'is_deleted' => false,
            ]
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \RuntimeException());
        $this->envelopeViewRepository->expects($this->once())->method('findOneBy')->willReturn($envelopeView);
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNameAlreadyExistsForUserException::class);
        $this->expectExceptionMessage(BudgetEnvelopeNameAlreadyExistsForUserException::MESSAGE);

        $this->createABudgetEnvelopeCommandHandler->__invoke($createABudgetEnvelopeCommand);
    }

    public function testCreateEnvelopeWithSameUuidFailure(): void
    {
        $createABudgetEnvelopeInput = new CreateABudgetEnvelopeInput(
            '0099c0ce-3b53-4318-ba7b-994e437a859b',
            'test name',
            '200.00'
        );
        $createABudgetEnvelopeCommand = new CreateABudgetEnvelopeCommand(
            BudgetEnvelopeId::fromString($createABudgetEnvelopeInput->uuid),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
            BudgetEnvelopeName::fromString($createABudgetEnvelopeInput->name),
            BudgetEnvelopeTargetBudget::fromString($createABudgetEnvelopeInput->targetBudget),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            [
                [
                    'aggregate_id' => $createABudgetEnvelopeInput->uuid,
                    'type' => BudgetEnvelopeCreatedEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'name' => 'test1',
                        'userId' => 'a871e446-ddcd-4e7a-9bf9-525bab84e566',
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => $createABudgetEnvelopeInput->uuid,
                        'targetBudget' => '20.00',
                    ]),
                ],
            ],
        );
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeAlreadyExistsException::class);
        $this->expectExceptionMessage(BudgetEnvelopeAlreadyExistsException::MESSAGE);

        $this->createABudgetEnvelopeCommandHandler->__invoke($createABudgetEnvelopeCommand);
    }
}
