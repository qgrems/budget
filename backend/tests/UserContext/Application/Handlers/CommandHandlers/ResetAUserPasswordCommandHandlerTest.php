<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Gateway\User\Presentation\HTTP\DTOs\ResetAUserPasswordInput;
use App\Libraries\Anonymii\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\SharedContext\Domain\Services\EventClassMap;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserContext\Application\Commands\ResetAUserPasswordCommand;
use App\UserContext\Application\Handlers\CommandHandlers\ResetAUserPasswordCommandHandler;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
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
    private EventEncryptorInterface&MockObject $eventEncryptor;
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PasswordHasherInterface&MockObject $passwordHasher;
    private EventSourcedRepository $eventSourcedRepository;
    private ResetAUserPasswordCommandHandler $handler;
    private EventClassMap $eventClassMap;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventStore = $this->createMock(EventStoreInterface::class);
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(PasswordHasherInterface::class);
        $this->eventSourcedRepository = new EventSourcedRepository($this->eventStore);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->eventClassMap = new EventClassMap();
        $this->handler = new ResetAUserPasswordCommandHandler(
            $this->userViewRepository,
            $this->eventSourcedRepository,
            $this->passwordHasher,
            $this->eventEncryptor,
            $this->eventClassMap,
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
            new UserView(
                UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
                UserEmail::fromString('test@mail.com'),
                UserPassword::fromString('password'),
                UserFirstname::fromString('Test firstName'),
                UserLastname::fromString('Test lastName'),
                UserLanguagePreference::fromString('fr'),
                UserConsent::fromBool(true),
                new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
                new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
                new \DateTime('2024-12-07T22:03:35+00:00'),
                ['ROLE_USER'],
            ),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'event_name' => UserSignedUpDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                        'payload' => json_encode([
                            'email' => 'test@mail.com',
                            'password' => 'password',
                            'firstname' => 'Test firstName',
                            'lastname' => 'Test lastName',
                            'languagePreference' => 'fr',
                            'isConsentGiven' => true,
                            'isDeleted' => false,
                            'occurredOn' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                            'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'userId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'roles' => ['ROLE_USER'],
                        ]),
                    ],
                    [
                        'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'event_name' => UserPasswordResetRequestedDomainEvent::class,
                        'stream_version' => 1,
                        'occurred_on' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                        'payload' => json_encode([
                            'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'passwordResetToken' => 'token',
                            'passwordResetTokenExpiry' => new \DateTimeImmutable('+1 day')->format(\DateTime::ATOM),
                            'userId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'occurredOn' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                        ]),
                    ],
                ],
            ),
        );

        $this->eventEncryptor->expects($this->any())->method('decrypt')->willReturnCallback(
            static fn (UserSignedUpDomainEvent|UserPasswordResetRequestedDomainEvent $event) => $event,
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
            new UserView(
                UserId::fromString('7ac32191-3fa0-4477-8eb2-8dd3b0b7c836'),
                UserEmail::fromString('test@mail.com'),
                UserPassword::fromString('password'),
                UserFirstname::fromString('Test firstName'),
                UserLastname::fromString('Test lastName'),
                UserLanguagePreference::fromString('fr'),
                UserConsent::fromBool(true),
                new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
                new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
                new \DateTime('2024-12-07T22:03:35+00:00'),
                ['ROLE_USER'],
            ),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'event_name' => UserSignedUpDomainEvent::class,
                        'stream_version' => 0,
                        'occurred_on' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                        'payload' => json_encode([
                            'email' => 'test@mail.com',
                            'password' => 'password',
                            'firstname' => 'Test firstName',
                            'lastname' => 'Test lastName',
                            'languagePreference' => 'fr',
                            'isConsentGiven' => true,
                            'isDeleted' => false,
                            'occurredOn' => new \DateTimeImmutable()->format(\DateTime::ATOM),
                            'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'userId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'roles' => ['ROLE_USER'],
                        ]),
                    ],
                ],
            ),
        );

        $this->eventEncryptor->expects($this->once())->method('decrypt')->willReturn(
            new UserSignedUpDomainEvent(
                '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                'test@mail.com',
                'password',
                'Test firstName',
                'Test lastName',
                'fr',
                true,
                ['ROLE_USER'],
                '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
            ),
        );

        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');

        $this->expectException(InvalidUserOperationException::class);

        $this->handler->__invoke($command);
    }
}
