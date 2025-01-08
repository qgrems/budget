<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserManagement\Application\Commands\DeleteAUserCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\DeleteAUserCommandHandler;
use App\UserManagement\Domain\Events\UserSignedUpEvent;
use App\UserManagement\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private DeleteAUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new DeleteAUserCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testDeleteUserSuccess(): void
    {
        $command = new DeleteAUserCommand(UserId::fromString('10a33b8c-853a-4df8-8fc9-e8bb00b78da4'));

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'type' => UserSignedUpEvent::class,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'email' => 'test@mail.com',
                            'password' => 'password',
                            'firstname' => 'Test firstName',
                            'lastname' => 'Test lastName',
                            'isConsentGiven' => true,
                            'isDeleted' => false,
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'roles' => ['ROLE_USER'],
                        ]),
                    ],
                ],
            ),
        );
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testDeleteUserWithWrongUser(): void
    {
        $command = new DeleteAUserCommand(UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'));

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                        'type' => UserSignedUpEvent::class,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'email' => 'test@mail.com',
                            'password' => 'password',
                            'firstname' => 'Test firstName',
                            'lastname' => 'Test lastName',
                            'isConsentGiven' => true,
                            'isDeleted' => false,
                            'occurredOn' => '2024-12-07T22:03:35+00:00',
                            'aggregateId' => '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                            'roles' => ['ROLE_USER'],
                        ]),
                    ],
                ],
            ),
        );
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(\RuntimeException::class);

        $this->handler->__invoke($command);
    }
}
