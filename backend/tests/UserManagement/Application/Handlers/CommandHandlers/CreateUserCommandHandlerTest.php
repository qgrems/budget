<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\UserManagement\Application\Commands\CreateUserCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\CreateUserCommandHandler;
use App\UserManagement\Domain\Exceptions\UserAlreadyExistsException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Presentation\HTTP\DTOs\CreateUserInput;
use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CreateUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventSourcedRepository $eventSourcedRepository;
    private CreateUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new CreateUserCommandHandler(
            $this->eventSourcedRepository,
            $this->userViewRepository,
            $this->passwordHasher,
        );
    }

    public function testCreateUserSuccess(): void
    {
        $createUserInput = new CreateUserInput('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836', 'test@example.com', 'password', 'John', 'Doe', true);
        $command = new CreateUserCommand(
            $createUserInput->getUuid(),
            $createUserInput->getEmail(),
            $createUserInput->getPassword(),
            $createUserInput->getFirstname(),
            $createUserInput->getLastname(),
            $createUserInput->isConsentGiven(),
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \RuntimeException());
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testCreateUserWithSameOldUserUuid(): void
    {
        $createUserInput = new CreateUserInput('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836', 'test@example.com', 'password', 'John', 'Doe', true);
        $command = new CreateUserCommand(
            $createUserInput->getUuid(),
            $createUserInput->getEmail(),
            $createUserInput->getPassword(),
            $createUserInput->getFirstname(),
            $createUserInput->getLastname(),
            $createUserInput->isConsentGiven(),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn([]);
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(UserAlreadyExistsException::class);

        $this->handler->__invoke($command);
    }

    public function testCreateUserAlreadyExists(): void
    {
        $createUserInput = new CreateUserInput(
            '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
            'test@example.com',
            'password',
            'John',
            'Doe',
            true,
        );
        $command = new CreateUserCommand(
            $createUserInput->getUuid(),
            $createUserInput->getEmail(),
            $createUserInput->getPassword(),
            $createUserInput->getFirstname(),
            $createUserInput->getLastname(),
            $createUserInput->isConsentGiven(),
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \RuntimeException());

        $this->userViewRepository->method('findOneBy')->willReturn(
            new UserView()
                ->setUuid('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836')
                ->setEmail('test@mail.com')
                ->setPassword('password')
                ->setFirstname('Test firstName')
                ->setLastname('Test lastName')
                ->setConsentGiven(true)
        );

        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(UserAlreadyExistsException::class);

        $this->handler->__invoke($command);
    }
}
