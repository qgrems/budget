<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\UserManagement\Application\Commands\UpdateAUserFirstnameCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\UpdateAUserFirstnameCommandHandler;
use App\UserManagement\Domain\Events\UserSignedUpEvent;
use App\UserManagement\Domain\ValueObjects\UserFirstname;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Presentation\HTTP\DTOs\UpdateAUserFirstnameInput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateAUserFirstnameCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private UpdateAUserFirstnameCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new UpdateAUserFirstnameCommandHandler(
            $this->eventSourcedRepository,
        );
    }

    public function testUpdateUserFirstnameSuccess(): void
    {
        $createUserInput = new UpdateAUserFirstnameInput('John');
        $command = new UpdateAUserFirstnameCommand(
            UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
            UserFirstname::fromString($createUserInput->firstname),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            [
                [
                    'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                    'type' => UserSignedUpEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'email' => 'test@gmail.com',
                        'roles' => ['ROLE_USER'],
                        'lastname' => 'Doe',
                        'password' => 'HAdFD97Xp[T!crjHi^Y%',
                        'firstname' => 'David',
                        'occurredOn' => '2024-12-13T00:26:48+00:00',
                        'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'isConsentGiven' => true,
                    ]),
                ],
            ],
        );

        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }
}
