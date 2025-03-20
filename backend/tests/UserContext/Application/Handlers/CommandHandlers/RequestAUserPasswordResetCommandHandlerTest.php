<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\FluxCapacitor\EventStore\Ports\EventStoreInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\UserContext\Application\Commands\RequestAUserPasswordResetCommand;
use App\UserContext\Application\Handlers\CommandHandlers\RequestAUserPasswordResetCommandHandler;
use App\UserContext\Domain\Aggregates\User;
use App\UserContext\Domain\Exceptions\UserNotFoundException;
use App\UserContext\Domain\Ports\Inbound\PasswordResetTokenGeneratorInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RequestAUserPasswordResetCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordResetTokenGeneratorInterface&MockObject $passwordResetTokenGenerator;
    private EventSourcedRepository $eventSourcedRepository;
    private RequestAUserPasswordResetCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordResetTokenGenerator = $this->createMock(PasswordResetTokenGeneratorInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new RequestAUserPasswordResetCommandHandler(
            $this->userViewRepository,
            $this->passwordResetTokenGenerator,
            $this->eventSourcedRepository
        );
    }

    public function testRequestPasswordResetSuccess(): void
    {
        $userId = '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836';
        $userEmail = 'test@example.com';
        $resetToken = 'generated-reset-token';
        $command = new RequestAUserPasswordResetCommand(UserEmail::fromString($userEmail));

        $userView = new UserView(
            UserId::fromString($userId),
            UserEmail::fromString($userEmail),
            UserPassword::fromString('password123'),
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
            UserPassword::fromString('password123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserLanguagePreference::fromString('en'),
            UserConsent::fromBool(true)
        );

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $userEmail])
            ->willReturn($userView);

        $this->passwordResetTokenGenerator->expects($this->once())
            ->method('generate')
            ->willReturn($resetToken);

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

    public function testRequestPasswordResetUserNotFound(): void
    {
        $userEmail = 'nonexistent@example.com';
        $command = new RequestAUserPasswordResetCommand(UserEmail::fromString($userEmail));

        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => $userEmail])
            ->willReturn(null);

        $this->passwordResetTokenGenerator->expects($this->never())
            ->method('generate');

        $this->eventStore->expects($this->never())
            ->method('load');

        $this->eventStore->expects($this->never())
            ->method('save');

        $this->expectException(UserNotFoundException::class);
        $this->handler->__invoke($command);
    }
}
