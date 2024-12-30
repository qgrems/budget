<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\UserManagement\Application\Commands\RequestUserPasswordResetCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\RequestUserPasswordResetCommandHandler;
use App\UserManagement\Domain\Events\UserCreatedEvent;
use App\UserManagement\Domain\Exceptions\UserNotFoundException;
use App\UserManagement\Domain\Ports\Inbound\PasswordResetTokenGeneratorInterface;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Presentation\HTTP\DTOs\RequestUserPasswordResetInput;
use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestUserPasswordResetCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordResetTokenGeneratorInterface&MockObject $passwordResetTokenGenerator;
    private EventSourcedRepository $eventSourcedRepository;
    private RequestUserPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordResetTokenGenerator = $this->createMock(PasswordResetTokenGeneratorInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new RequestUserPasswordResetCommandHandler(
            $this->userViewRepository,
            $this->passwordResetTokenGenerator,
            $this->eventSourcedRepository,
        );
    }

    public function testRequestUserPasswordResetSuccess(): void
    {
        $requestUserPasswordResetInput = new RequestUserPasswordResetInput('test@mail.com');
        $command = new RequestUserPasswordResetCommand($requestUserPasswordResetInput->getEmail());

        $this->userViewRepository->method('findOneBy')->willReturn(
            new UserView()
                ->setUuid('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836')
                ->setEmail('test@mail.com')
                ->setPassword('password')
                ->setFirstname('Test firstName')
                ->setLastname('Test lastName')
                ->setConsentGiven(true)
        );
        $this->eventStore->expects($this->once())->method('load')->willReturn(
            [
                [
                    'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                    'type' => UserCreatedEvent::class,
                    'occurred_on' => '2020-10-10T12:00:00Z',
                    'payload' => json_encode([
                        'email' => 'test@mail.com',
                        'password' => 'password',
                        'firstname' => 'Test firstName',
                        'lastname' => 'Test lastName',
                        'isConsentGiven' => true,
                        'isDeleted' => false,
                        'occurredOn' => '2024-12-07T22:03:35+00:00',
                        'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'roles' => ['ROLE_USER'],
                    ]),
                ],
            ],
        );

        $this->passwordResetTokenGenerator->method('generate')->willReturn('test');
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testRequestUserPasswordResetWithUserNotFound(): void
    {
        $requestUserPasswordResetInput = new RequestUserPasswordResetInput('test@mail.com');
        $command = new RequestUserPasswordResetCommand($requestUserPasswordResetInput->getEmail());

        $this->userViewRepository->method('findOneBy')->willReturn(
            null,
        );

        $this->expectException(UserNotFoundException::class);

        $this->handler->__invoke($command);
    }
}
