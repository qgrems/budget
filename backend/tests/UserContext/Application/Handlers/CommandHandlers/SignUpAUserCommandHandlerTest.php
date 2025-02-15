<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Application\Handlers\CommandHandlers;

use App\Libraries\Anonymii\Ports\EventEncryptorInterface;
use App\Libraries\FluxCapacitor\Ports\EventStoreInterface;
use App\Gateway\User\HTTP\DTOs\SignUpAUserInput;
use App\SharedContext\Infrastructure\Repositories\EventSourcedRepository;
use App\Tests\CreateEventGenerator;
use App\UserContext\Application\Commands\SignUpAUserCommand;
use App\UserContext\Application\Handlers\CommandHandlers\SignUpAUserCommandHandler;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\PasswordHasherInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SignUpAUserCommandHandlerTest extends TestCase
{
    private EventStoreInterface&MockObject $eventStore;
    private EventEncryptorInterface&MockObject $eventEncryptor;
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
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->handler = new SignUpAUserCommandHandler(
            $this->eventSourcedRepository,
            $this->userViewRepository,
            $this->passwordHasher,
            $this->eventEncryptor,
        );
    }

    public function testCreateUserSuccess(): void
    {
        $signUpAUserInput = new SignUpAUserInput(
            '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
            'test@example.com',
            'password',
            'John',
            'Doe',
            'fr',
            true,
        );
        $command = new SignUpAUserCommand(
            UserId::fromString($signUpAUserInput->uuid),
            UserEmail::fromString($signUpAUserInput->email),
            UserPassword::fromString($signUpAUserInput->password),
            UserFirstname::fromString($signUpAUserInput->firstname),
            UserLastname::fromString($signUpAUserInput->lastname),
            UserLanguagePreference::fromString($signUpAUserInput->languagePreference),
            UserConsent::fromBool($signUpAUserInput->consentGiven),
        );

        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->once())->method('save');

        $this->handler->__invoke($command);
    }

    public function testCreateUserWithSameOldUserUuid(): void
    {
        $signUpAUserInput = new SignUpAUserInput(
            '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
            'test@example.com',
            'password',
            'John',
            'Doe',
            'fr',
            true,
        );
        $command = new SignUpAUserCommand(
            UserId::fromString($signUpAUserInput->uuid),
            UserEmail::fromString($signUpAUserInput->email),
            UserPassword::fromString($signUpAUserInput->password),
            UserFirstname::fromString($signUpAUserInput->firstname),
            UserLastname::fromString($signUpAUserInput->lastname),
            UserLanguagePreference::fromString($signUpAUserInput->languagePreference),
            UserConsent::fromBool($signUpAUserInput->consentGiven),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(
            CreateEventGenerator::create(
                [
                    [
                        'aggregate_id' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                        'type' => UserSignedUpDomainEvent::class,
                        'occurred_on' => '2020-10-10T12:00:00Z',
                        'payload' => json_encode([
                            'email' => 'test@gmail.com',
                            'roles' => ['ROLE_USER'],
                            'lastname' => 'Doe',
                            'password' => 'HAdFD97Xp[T!crjHi^Y%',
                            'firstname' => 'David',
                            'languagePreference' => 'fr',
                            'occurredOn' => '2024-12-13T00:26:48+00:00',
                            'aggregateId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'userId' => '7ac32191-3fa0-4477-8eb2-8dd3b0b7c836',
                            'requestId' => '9faff004-117b-4b51-8e4d-ed6648f745c2',
                            'isConsentGiven' => true,
                        ]),
                    ],
                ],
            ),
        );
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
            'fr',
            true,
        );
        $command = new SignUpAUserCommand(
            UserId::fromString($signUpAUserInput->uuid),
            UserEmail::fromString($signUpAUserInput->email),
            UserPassword::fromString($signUpAUserInput->password),
            UserFirstname::fromString($signUpAUserInput->firstname),
            UserLastname::fromString($signUpAUserInput->lastname),
            UserLanguagePreference::fromString($signUpAUserInput->languagePreference),
            UserConsent::fromBool($signUpAUserInput->consentGiven),
        );

        $this->eventStore->expects($this->once())->method('load')->willReturn(CreateEventGenerator::create([]));

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

        $this->passwordHasher->method('hash')->willReturn('hashed-new-password');
        $this->eventStore->expects($this->never())->method('save');
        $this->expectException(UserAlreadyExistsException::class);

        $this->handler->__invoke($command);
    }
}
