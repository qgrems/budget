<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\ResetAUserPasswordCommand;
use App\UserContext\Application\Handlers\CommandHandlers\ResetAUserPasswordCommandHandler;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\InvalidUserOperationException;
use App\UserContext\Domain\Exceptions\UserNotFoundException;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;
use App\UserContext\ReadModels\Views\UserView;
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
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $userEmail = 'test@example.com';
        $resetToken = 'valid-reset-token';
        $newPassword = 'newPassword123';
        $hashedPassword = 'hashed_newPassword123';

        $command = new ResetAUserPasswordCommand(
            UserPasswordResetToken::fromString($resetToken),
            UserPassword::fromString($newPassword)
        );

        $userView = new UserView(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserPassword::fromString('oldPassword123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTime(),
            ['ROLE_USER']
        );

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserPassword::fromString('oldPassword123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true)
        );

        $reflection = new \ReflectionClass($user);
        $passwordResetTokenProperty = $reflection->getProperty('passwordResetToken');
        $passwordResetTokenProperty->setAccessible(true);
        $passwordResetTokenProperty->setValue($user, UserPasswordResetToken::fromString($resetToken));

        $passwordResetTokenExpiryProperty = $reflection->getProperty('passwordResetTokenExpiry');
        $passwordResetTokenExpiryProperty->setAccessible(true);
        $passwordResetTokenExpiryProperty->setValue($user, new \DateTimeImmutable('+1 hour'));

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordResetToken' => $resetToken])
            ->willReturn($userView);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn($hashedPassword);

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($user);

        $this->eventStore->expects($this->once())
            ->method('save')
            ->with($this->callback(function ($savedUser) {
                return $savedUser instanceof User;
            }));

        $this->handler->__invoke($command);
    }

    public function testResetUserPasswordUserNotFound(): void
    {
        $resetToken = 'invalid-reset-token';
        $newPassword = 'newPassword123';

        $command = new ResetAUserPasswordCommand(
            UserPasswordResetToken::fromString($resetToken),
            UserPassword::fromString($newPassword)
        );

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordResetToken' => $resetToken])
            ->willReturn(null);

        $this->passwordHasher->expects($this->never())
            ->method('hash');

        $this->eventStore->expects($this->never())
            ->method('load');

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(UserNotFoundException::class);
        $this->handler->__invoke($command);
    }

    public function testResetUserPasswordTokenExpired(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $userEmail = 'test@example.com';
        $resetToken = 'expired-reset-token';
        $newPassword = 'newPassword123';

        $command = new ResetAUserPasswordCommand(
            UserPasswordResetToken::fromString($resetToken),
            UserPassword::fromString($newPassword)
        );

        $userView = new UserView(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserPassword::fromString('oldPassword123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTime(),
            ['ROLE_USER']
        );

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserPassword::fromString('oldPassword123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true)
        );

        $reflection = new \ReflectionClass($user);
        $passwordResetTokenProperty = $reflection->getProperty('passwordResetToken');
        $passwordResetTokenProperty->setAccessible(true);
        $passwordResetTokenProperty->setValue($user, UserPasswordResetToken::fromString($resetToken));

        $passwordResetTokenExpiryProperty = $reflection->getProperty('passwordResetTokenExpiry');
        $passwordResetTokenExpiryProperty->setAccessible(true);
        $passwordResetTokenExpiryProperty->setValue($user, new \DateTimeImmutable('-1 hour'));

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordResetToken' => $resetToken])
            ->willReturn($userView);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn('hashed_password');

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($user);

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(InvalidUserOperationException::class);
        $this->handler->__invoke($command);
    }

    public function testResetUserPasswordUserNotFoundInEventStore(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $userEmail = 'test@example.com';
        $resetToken = 'valid-reset-token';
        $newPassword = 'newPassword123';

        $command = new ResetAUserPasswordCommand(
            UserPasswordResetToken::fromString($resetToken),
            UserPassword::fromString($newPassword)
        );

        $userView = new UserView(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserPassword::fromString('oldPassword123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTime(),
            ['ROLE_USER']
        );

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['passwordResetToken' => $resetToken])
            ->willReturn($userView);

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(EventsNotFoundForAggregateException::class);
        $this->handler->__invoke($command);
    }
}
