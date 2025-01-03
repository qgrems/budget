<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Application\Handlers\CommandHandlers;

use App\SharedContext\EventStore\EventStoreInterface;
use App\SharedContext\Infrastructure\Persistence\Repositories\EventSourcedRepository;
use App\UserManagement\Application\Commands\UpdateAUserPasswordCommand;
use App\UserManagement\Application\Handlers\CommandHandlers\UpdateAUserPasswordCommandHandler;
use App\UserManagement\Domain\Events\UserSignedUpEvent;
use App\UserManagement\Domain\Exceptions\UserOldPasswordIsIncorrectException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserPassword;
use App\UserManagement\Presentation\HTTP\DTOs\UpdateAUserPasswordInput;
use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateAUserPasswordCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventSourcedRepository $eventSourcedRepository;
    private UpdateAUserPasswordCommandHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->handler = new UpdateAUserPasswordCommandHandler(
            $this->eventSourcedRepository,
            $this->userViewRepository,
            $this->passwordHasher,
        );
    }

    public function testUpdateUserPasswordSuccess(): void
    {
        $updateUserPasswordInput = new UpdateAUserPasswordInput('password', '87654321');
        $command = new UpdateAUserPasswordCommand(
            UserPassword::fromString($updateUserPasswordInput->oldPassword),
            UserPassword::fromString($updateUserPasswordInput->newPassword),
            UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            [
                [
                    'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                    'type' => UserSignedUpEvent::class,
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

        $this->userViewRepository->method('findOneBy')->willReturn(
            new UserView()
                ->setEmail('test@mail.com')
                ->setPassword('password')
                ->setFirstname('Test firstName')
                ->setLastname('Test lastName')
                ->setConsentGiven(true),
        );
        $this->passwordHasher->method('verify')->willReturn(true);
        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testUpdateUserPasswordWithFakeOldPassword(): void
    {
        $updateUserPasswordInput = new UpdateAUserPasswordInput('fakeoldpassword', '87654321');
        $command = new UpdateAUserPasswordCommand(
            UserPassword::fromString($updateUserPasswordInput->oldPassword),
            UserPassword::fromString($updateUserPasswordInput->newPassword),
            UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            [
                [
                    'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                    'type' => UserSignedUpEvent::class,
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

        $this->userViewRepository->method('findOneBy')->willReturn(
            new UserView()
                ->setEmail('test@mail.com')
                ->setPassword('password')
                ->setFirstname('Test firstName')
                ->setLastname('Test lastName')
                ->setConsentGiven(true),
        );
        $this->passwordHasher->method('verify')->willReturn(false);
        $this->expectException(UserOldPasswordIsIncorrectException::class);

        $this->handler->__invoke($command);
    }
}
