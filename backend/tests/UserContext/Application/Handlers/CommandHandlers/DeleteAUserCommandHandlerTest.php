<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserContext\Application\Commands\DeleteAUserCommand;
use App\UserContext\Application\Handlers\CommandHandlers\DeleteAUserCommandHandler;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteAUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventEncryptorInterface&MockObject $eventEncryptor;
    private EventSourcedRepository $eventSourcedRepository;
    private DeleteAUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->handler = new DeleteAUserCommandHandler(
            $this->eventSourcedRepository,
            $this->eventEncryptor,
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
                        'type' => UserSignedUpDomainEvent::class,
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
        $this->eventEncryptor->expects($this->once())->method('decrypt')->willReturn(
            new UserSignedUpDomainEvent(
                '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                'test@mail.com',
                'password',
                'Test firstName',
                'Test lastName',
                true,
                ['ROLE_USER'],
            ),
        );

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
                        'type' => UserSignedUpDomainEvent::class,
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
        $this->eventEncryptor->expects($this->once())->method('decrypt')->willReturn(
            new UserSignedUpDomainEvent(
                '10a33b8c-853a-4df8-8fc9-e8bb00b78da4',
                'test@mail.com',
                'password',
                'Test firstName',
                'Test lastName',
                true,
                ['ROLE_USER'],
            ),
        );
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(UserIsNotOwnedByUserException::class);

        $this->handler->__invoke($command);
    }
}
