<?php

declare(strict_types=1);

namespace App\Tests\BudgetEnvelopeContext\Application\Handlers\CommandHandlers;

use App\BudgetEnvelopeContext\Application\Commands\RenameABudgetEnvelopeCommand;
use App\BudgetEnvelopeContext\Application\Handlers\CommandHandlers\RenameABudgetEnvelopeCommandHandler;
use App\BudgetEnvelopeContext\Domain\Events\BudgetEnvelopeCreatedEvent;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeIsNotOwnedByUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNameAlreadyExistsForUserException;
use App\BudgetEnvelopeContext\Domain\Exceptions\BudgetEnvelopeNotFoundException;
use App\BudgetEnvelopeContext\Domain\Ports\Inbound\BudgetEnvelopeViewRepositoryInterface;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeId;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeName;
use App\BudgetEnvelopeContext\Domain\ValueObjects\BudgetEnvelopeUserId;
use App\BudgetEnvelopeContext\Presentation\HTTP\DTOs\RenameABudgetEnvelopeInput;
use App\BudgetEnvelopeContext\ReadModels\Views\BudgetEnvelopeView;
use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RenameABudgetEnvelopeCommandHandlerTest extends TestCase
{
    private RenameABudgetEnvelopeCommandHandler $renameABudgetEnvelopeCommandHandler;
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private BudgetEnvelopeViewRepositoryInterface $envelopeViewRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->envelopeViewRepository = $this->createMock(BudgetEnvelopeViewRepositoryInterface::class);

        $this->renameABudgetEnvelopeCommandHandler = new RenameABudgetEnvelopeCommandHandler(
            $this->eventSourcedRepository,
            $this->envelopeViewRepository,
        );
    }

    public function testRenameABudgetEnvelopeSuccess(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput(
            'test',
        );
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
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
                    ],
                ),
            );
        $this->eventStore->expects($this->once())->method('save');

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameABudgetEnvelopeWithSameEnvelopeName(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput(
            'test',
        );
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
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
                    ],
                ),
            );

        $this->envelopeViewRepository->expects($this->once())->method('findOneBy')->willReturn(
            BudgetEnvelopeView::fromRepository(
                [
                    'uuid' => 'be0c3a86-c3c9-467f-b675-3f519fd96111',
                    'name' => 'test',
                    'targeted_amount' => '300.00',
                    'current_amount' => '150.00',
                    'user_uuid' => 'd26cc02e-99e7-428c-9d61-572dff3f84a7',
                    'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'is_deleted' => false,
                ],
            ),
        );
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeNameAlreadyExistsForUserException::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameABudgetEnvelopeNotFoundFailure(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput(
            'test',
        );
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
            BudgetEnvelopeId::fromString('0099c0ce-3b53-4318-ba7b-994e437a859b'),
            BudgetEnvelopeUserId::fromString('d26cc02e-99e7-428c-9d61-572dff3f84a7'),
        );

        $this->eventStore->expects($this->once())->method('load')
            ->willThrowException(new BudgetEnvelopeNotFoundException());
        $this->eventStore->expects($this->never())->method('save');

        $this->expectException(BudgetEnvelopeNotFoundException::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }

    public function testRenameABudgetEnvelopeWithWrongUser(): void
    {
        $renameABudgetEnvelopeInput = new RenameABudgetEnvelopeInput(
            'test',
        );
        $renameABudgetEnvelopeCommand = new RenameABudgetEnvelopeCommand(
            BudgetEnvelopeName::fromString($renameABudgetEnvelopeInput->name),
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
                    ],
                ),
            );

        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(BudgetEnvelopeIsNotOwnedByUserException::class);

        $this->renameABudgetEnvelopeCommandHandler->__invoke($renameABudgetEnvelopeCommand);
    }
}
