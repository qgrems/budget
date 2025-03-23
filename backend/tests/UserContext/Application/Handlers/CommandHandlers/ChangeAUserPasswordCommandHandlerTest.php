<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\ChangeAUserPasswordCommand;
use App\UserContext\Application\Handlers\CommandHandlers\ChangeAUserPasswordCommandHandler;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\UserOldPasswordIsIncorrectException;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ChangeAUserPasswordCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventSourcedRepository $eventSourcedRepository;
    private ChangeAUserPasswordCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new ChangeAUserPasswordCommandHandler(
            $this->eventSourcedRepository,
            $this->userViewRepository,
            $this->passwordHasher
        );
    }

    public function testUpdateUserPasswordSuccess(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $oldPassword = 'password123';
        $newPassword = '87654321';

        $command = new ChangeAUserPasswordCommand(
            UserPassword::fromString($oldPassword),
            UserPassword::fromString($newPassword),
            UserId::fromString($userId)
        );

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString('test@example.com'),
            UserPassword::fromString($oldPassword),
            UserFirstname::fromString('David'),
            UserLastname::fromString('Smith'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true)
        );

        $userView = new UserView(
            UserId::fromString($userId),
            UserEmail::fromString('test@example.com'),
            UserPassword::fromString($oldPassword),
            UserFirstname::fromString('David'),
            UserLastname::fromString('Smith'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTime(),
            ['ROLE_USER']
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($user);

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $userId])
            ->willReturn($userView);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->passwordHasher->expects($this->once())
            ->method('hash')
            ->willReturn('87654321');

        $this->handler->__invoke($command);
    }

    public function testUpdateUserPasswordWithFakeOldPassword(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $oldPassword = 'fakeoldpassword';
        $newPassword = '87654321';

        $command = new ChangeAUserPasswordCommand(
            UserPassword::fromString($oldPassword),
            UserPassword::fromString($newPassword),
            UserId::fromString($userId)
        );

        $user = User::create(
            UserId::fromString($userId),
            UserEmail::fromString('test@example.com'),
            UserPassword::fromString('password123'), // Different from what will be provided
            UserFirstname::fromString('David'),
            UserLastname::fromString('Smith'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true)
        );

        $userView = new UserView(
            UserId::fromString($userId),
            UserEmail::fromString('test@example.com'),
            UserPassword::fromString('password123'),
            UserFirstname::fromString('David'),
            UserLastname::fromString('Smith'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable(),
            new \DateTimeImmutable(),
            new \DateTime(),
            ['ROLE_USER']
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willReturn($user);

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $userId])
            ->willReturn($userView);

        $this->passwordHasher->expects($this->once())
            ->method('verify')
            ->willReturn(false);

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(UserOldPasswordIsIncorrectException::class);
        $this->handler->__invoke($command);
    }

    public function testUpdateUserPasswordUserNotFound(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $oldPassword = 'password123';
        $newPassword = '87654321';

        $command = new ChangeAUserPasswordCommand(
            UserPassword::fromString($oldPassword),
            UserPassword::fromString($newPassword),
            UserId::fromString($userId)
        );

        $this->eventStore->expects($this->once())
            ->method('load')
            ->with($userId)
            ->willThrowException(new EventsNotFoundForAggregateException());

        $this->userViewRepository->expects($this->never())
            ->method('findOneBy');

        $this->passwordHasher->expects($this->never())
            ->method('verify');

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(EventsNotFoundForAggregateException::class);
        $this->handler->__invoke($command);
    }
}
