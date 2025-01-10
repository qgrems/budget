<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\Domain\Ports\Inbound\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserManagement\Application\Commands\ResetAUserPasswordCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\ResetAUserPasswordCommandHandler;
use App\UserManagement\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserManagement\Domain\Events\UserSignedUpEvent;
use App\UserManagement\Domain\Exceptions\InvalidUserOperationException;
use App\UserManagement\Domain\Exceptions\UserNotFoundException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\UserPassword;
use App\UserManagement\Domain\ValueObjects\UserPasswordResetToken;
use App\UserManagement\Presentation\HTTP\DTOs\ResetAUserPasswordInput;
use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ResetAUserPasswordCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventSourcedRepository $eventSourcedRepository;
    private ResetAUserPasswordCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new ResetAUserPasswordCommandHandler(
            $this->userViewRepository,
            $this->eventSourcedRepository,
            $this->passwordHasher,
        );
    }

    public function testResetUserPasswordSuccess(): void
    {
        $resetUserPasswordInput = new ResetAUserPasswordInput('token', 'password');
        $command = new ResetAUserPasswordCommand(
            UserPasswordResetToken::fromString($resetUserPasswordInput->token),
            UserPassword::fromString($resetUserPasswordInput->newPassword),
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
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'type' => UserSignedUpEvent::class,
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
            ),
        );
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testResetUserPasswordWithUserNotFound(): void
    {
        $resetUserPasswordInput = new ResetAUserPasswordInput('token', 'password');
        $command = new ResetAUserPasswordCommand(
            UserPasswordResetToken::fromString($resetUserPasswordInput->token),
            UserPassword::fromString($resetUserPasswordInput->newPassword),
        );

        $this->userViewRepository->method('findOneBy')->willReturn(
            null,
        );
        $this->expectException(UserNotFoundException::class);

        $this->handler->__invoke($command);
    }

    public function testResetUserPasswordWithExpiryDateWrong(): void
    {
        $resetUserPasswordInput = new ResetAUserPasswordInput('token', 'password');
        $command = new ResetAUserPasswordCommand(
            UserPasswordResetToken::fromString($resetUserPasswordInput->token),
            UserPassword::fromString($resetUserPasswordInput->newPassword),
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
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'type' => UserSignedUpEvent::class,
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
            ),
        );
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');

        $this->expectException(InvalidUserOperationException::class);

        $this->handler->__invoke($command);
    }
}
