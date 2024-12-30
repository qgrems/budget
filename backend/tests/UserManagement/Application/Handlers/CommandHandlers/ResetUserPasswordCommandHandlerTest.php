<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\UserManagement\Application\Commands\ResetUserPasswordCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\ResetUserPasswordCommandHandler;
use App\UserManagement\Domain\Events\UserCreatedEvent;
use App\UserManagement\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserManagement\Domain\Exceptions\InvalidUserOperationException;
use App\UserManagement\Domain\Exceptions\UserNotFoundException;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Infrastructure\Persistence\Repositories\UserViewRepository;
use App\UserManagement\Presentation\HTTP\DTOs\ResetUserPasswordInput;
use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResetUserPasswordCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepository&MockObject $userViewRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventSourcedRepository $eventSourcedRepository;
    private ResetUserPasswordCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepository::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new ResetUserPasswordCommandHandler(
            $this->userViewRepository,
            $this->eventSourcedRepository,
            $this->passwordHasher,
        );
    }

    public function testResetUserPasswordSuccess(): void
    {
        $resetUserPasswordInput = new ResetUserPasswordInput('token', 'password');
        $command = new ResetUserPasswordCommand(
            $resetUserPasswordInput->getToken(),
            $resetUserPasswordInput->getNewPassword(),
        );

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
                    'occurred_on' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                    'payload' => json_encode([
                        'email' => 'test@mail.com',
                        'password' => 'password',
                        'firstname' => 'Test firstName',
                        'lastname' => 'Test lastName',
                        'isConsentGiven' => true,
                        'isDeleted' => false,
                        'occurredOn' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                        'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'roles' => ['ROLE_USER'],
                    ]),
                ],
                [
                    'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                    'type' => UserPasswordResetRequestedEvent::class,
                    'occurred_on' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                    'payload' => json_encode([
                        'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'passwordResetToken' => 'token',
                        'passwordResetTokenExpiry' => new \DateTimeImmutable('+1 day')->format(\DateTime::ATOM),
                        'occurredOn' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                    ]),
                ],
            ],
        );
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testResetUserPasswordWithUserNotFound(): void
    {
        $resetUserPasswordInput = new ResetUserPasswordInput('token', 'password');
        $command = new ResetUserPasswordCommand(
            $resetUserPasswordInput->getToken(),
            $resetUserPasswordInput->getNewPassword(),
        );

        $this->userViewRepository->method('findOneBy')->willReturn(
            null,
        );
        $this->expectException(UserNotFoundException::class);

        $this->handler->__invoke($command);
    }

    public function testResetUserPasswordWithExpiryDateWrong(): void
    {
        $resetUserPasswordInput = new ResetUserPasswordInput('token', 'password');
        $command = new ResetUserPasswordCommand(
            $resetUserPasswordInput->getToken(),
            $resetUserPasswordInput->getNewPassword(),
        );

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
                    'occurred_on' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                    'payload' => json_encode([
                        'email' => 'test@mail.com',
                        'password' => 'password',
                        'firstname' => 'Test firstName',
                        'lastname' => 'Test lastName',
                        'isConsentGiven' => true,
                        'isDeleted' => false,
                        'occurredOn' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                        'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'roles' => ['ROLE_USER'],
                    ]),
                ],
            ],
        );
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');

        $this->expectException(InvalidUserOperationException::class);

        $this->handler->__invoke($command);
    }
}
