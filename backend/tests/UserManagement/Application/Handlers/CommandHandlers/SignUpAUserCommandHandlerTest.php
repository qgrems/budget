<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\UserManagement\Application\Commands\SignUpAUserCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\SignUpAUserCommandHandler;
use App\UserManagement\Domain\Exceptions\UserAlreadyExistsException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\UserConsent;
use App\UserManagement\Domain\ValueObjects\UserEmail;
use App\UserManagement\Domain\ValueObjects\UserFirstname;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserLastname;
use App\UserManagement\Domain\ValueObjects\UserPassword;
use App\UserManagement\Presentation\HTTP\DTOs\SignUpAUserInput;
use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignUpAUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventSourcedRepository $eventSourcedRepository;
    private SignUpAUserCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new SignUpAUserCommandHandler(
            $this->eventSourcedRepository,
            $this->userViewRepository,
            $this->passwordHasher,
        );
    }

    public function testCreateUserSuccess(): void
    {
        $signUpAUserInput = new SignUpAUserInput('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836', 'test@example.com', 'password', 'John', 'Doe', true);
        $command = new SignUpAUserCommand(
            UserId::fromString($signUpAUserInput->uuid),
            UserEmail::fromString($signUpAUserInput->email),
            UserPassword::fromString($signUpAUserInput->password),
            UserFirstname::fromString($signUpAUserInput->firstname),
            UserLastname::fromString($signUpAUserInput->lastname),
            UserConsent::fromBool($signUpAUserInput->consentGiven),
        );

        $this->eventStore->expects($this->once())->method('load')->willThrowException(new \RuntimeException());
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testCreateUserWithSameOldUserUuid(): void
    {
        $signUpAUserInput = new SignUpAUserInput('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836', 'test@example.com', 'password', 'John', 'Doe', true);
        $command = new SignUpAUserCommand(
            UserId::fromString($signUpAUserInput->uuid),
            UserEmail::fromString($signUpAUserInput->email),
            UserPassword::fromString($signUpAUserInput->password),
            UserFirstname::fromString($signUpAUserInput->firstname),
            UserLastname::fromString($signUpAUserInput->lastname),
            UserConsent::fromBool($signUpAUserInput->consentGiven),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn([]);
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(UserAlreadyExistsException::class);

        $this->handler->__invoke($command);
    }

    public function testCreateUserAlreadyExists(): void
    {
        $signUpAUserInput = new SignUpAUserInput(
            '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
            'test@example.com',
            'password',
            'John',
            'Doe',
            true,
        );
        $command = new SignUpAUserCommand(
            UserId::fromString($signUpAUserInput->uuid),
            UserEmail::fromString($signUpAUserInput->email),
            UserPassword::fromString($signUpAUserInput->password),
            UserFirstname::fromString($signUpAUserInput->firstname),
            UserLastname::fromString($signUpAUserInput->lastname),
            UserConsent::fromBool($signUpAUserInput->consentGiven),
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
