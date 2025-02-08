<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Domain\Services\EventClassMap;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserContext\Application\Commands\ChangeAUserLastnameCommand;
use App\UserContext\Application\Handlers\CommandHandlers\ChangeAUserLastnameCommandHandler;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Presentation\HTTP\DTOs\ChangeAUserLastnameInput;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeAUserLastnameCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventEncryptorInterface&MockObject $eventEncryptor;
    private EventSourcedRepository $eventSourcedRepository;
    private ChangeAUserLastnameCommandHandler $handler;
    private EventClassMap $eventClassMap;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->eventClassMap = new EventClassMap();
        $this->handler = new ChangeAUserLastnameCommandHandler(
            $this->eventSourcedRepository,
            $this->eventEncryptor,
            $this->eventClassMap,
        );
    }

    public function testUpdateUserLastnameSuccess(): void
    {
        $createUserInput = new ChangeAUserLastnameInput('Snow');
        $command = new ChangeAUserLastnameCommand(
            UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
            UserLastname::fromString($createUserInput->lastname),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'event_name' => UserSignedUpDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'email' => 'test@gmail.com',
                            'roles' => ['ROLE_USER'],
                            'lastname' => 'Doe',
                            'languagePreference' => 'fr',
                            'password' => 'HAdFD97Xp[T!crjHi^Y%',
                            'firstname' => 'David',
                            'occurredOn' => '2024-12-13T00:26:48+00:00',
                            'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'userId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
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
                'fr',
                true,
                ['ROLE_USER'],
                '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
            ),
        );

        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }
}
