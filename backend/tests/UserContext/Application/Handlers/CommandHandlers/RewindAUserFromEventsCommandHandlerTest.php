<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserContext\Application\Commands\RewindAUserFromEventsCommand;
use App\UserContext\Application\Handlers\CommandHandlers\RewindAUserFromEventsCommandHandler;
use App\UserContext\Domain\Events\UserSignedUpEvent;
use App\UserContext\Domain\ValueObjects\UserId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RewindAUserFromEventsCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventSourcedRepository $eventSourcedRepository;
    private RewindAUserFromEventsCommandHandler $handler;

    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new RewindAUserFromEventsCommandHandler($this->eventSourcedRepository);
    }

    public function testReplaySuccess(): void
    {
        $command = new RewindAUserFromEventsCommand(
            UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
            new \DateTimeImmutable('2020-10-10T12:00:00Z'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($command->getUserId())
            ->willReturn(
                CreateEventGenerator::create(
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
                ),
            );

        $this->handler->__invoke($command);
    }

    public function testReplayFailure(): void
    {
        $command = new RewindAUserFromEventsCommand(
            UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
            new \DateTimeImmutable('2020-10-10T12:00:00Z'),
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($command->getUserId())
            ->willThrowException(new \Exception('Error loading events'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Error loading events');

        $this->handler->__invoke($command);
    }
}
