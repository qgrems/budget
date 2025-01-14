<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserContext\Application\Commands\ReplayAUserEventsCommand;
use App\UserContext\Application\Handlers\CommandHandlers\ReplayAUserEventsCommandHandler;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReplayAUserEventsCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private ReplayAUserEventsCommandHandler $handler;
    private EventEncryptorInterface&MockObject $eventEncryptor;

    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->handler = new ReplayAUserEventsCommandHandler($this->eventSourcedRepository, $this->eventEncryptor);
    }

    public function testReplaySuccess(): void
    {
        $command = new ReplayAUserEventsCommand(UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'));

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($command->getUserId())
            ->willReturn(
                CreateEventGenerator::create(
                    [
                        [
                            'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'type' => UserSignedUpDomainEvent::class,
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
                ),
            );
        $this->eventEncryptor->expects($this->once())->method('decrypt')->willReturn(
            new UserSignedUpDomainEvent(
                '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                'test@mail.com',
                'HAdFD97Xp[T!crjHi^Y%',
                'David',
                'Doe',
                true,
                ['ROLE_USER'],
            ),
        );

        $this->handler->__invoke($command);
    }

    public function testReplayFailure(): void
    {
        $command = new ReplayAUserEventsCommand(UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'));

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($command->getUserId())
            ->willThrowException(new \Exception('Error loading events'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error loading events');

        $this->handler->__invoke($command);
    }
}
