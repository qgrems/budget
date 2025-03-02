<?php

declare(strict_types=1);

namespace App\Tests\UserContext\ReadModels\Projections;

use App\Libraries\Anonymii\Ports\EventEncryptorInterface;
use App\Libraries\Anonymii\Ports\KeyManagementRepositoryInterface;
use App\SharedContext\Domain\Ports\Outbound\PublisherInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Ports\Outbound\MailerInterface;
use App\UserContext\Domain\Ports\Outbound\RefreshTokenManagerInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\ReadModels\Projections\UserProjection;
use App\UserContext\ReadModels\Views\UserView;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UserProjectionTest extends TestCase
{
    private UserViewRepositoryInterface&MockObject $userViewRepository;
    private PublisherInterface&MockObject $publisher;
    private MailerInterface&MockObject $mailer;
    private UserProjection $userProjection;
    private KeyManagementRepositoryInterface&MockObject $keyManagementRepository;
    private EventEncryptorInterface&MockObject $eventEncryptor;
    private RefreshTokenManagerInterface&MockObject $refreshTokenManager;

    protected function setUp(): void
    {
        $this->userViewRepository = $this->createMock(UserViewRepositoryInterface::class);
        $this->publisher = $this->createMock(PublisherInterface::class);
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->keyManagementRepository = $this->createMock(KeyManagementRepositoryInterface::class);
        $this->eventEncryptor = $this->createMock(EventEncryptorInterface::class);
        $this->refreshTokenManager = $this->createMock(RefreshTokenManagerInterface::class);
        $this->userProjection = new UserProjection(
            $this->userViewRepository,
            $this->mailer,
            $this->keyManagementRepository,
            $this->eventEncryptor,
            $this->refreshTokenManager,
            $this->publisher,
        );

        $this->eventEncryptor->method('decrypt')->willReturnCallback(
            function ($event) {
                return $event;
            }
        );
    }

    public function testEncryptionKeyDoesNotExist(): void
    {
        $event = new UserSignedUpDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'john.doe@example.com',
            'password123',
            'John',
            'Doe',
            'fr',
            true,
            ['ROLE_USER'],
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->expects($this->once())
            ->method('getKey')
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserSignedUpEvent(): void
    {
        $event = new UserSignedUpDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'john.doe@example.com',
            'password123',
            'John',
            'Doe',
            'fr',
            true,
            ['ROLE_USER'],
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (UserView $view) use ($event) {
                return $view->uuid === $event->aggregateId
                    && $view->createdAt == $event->occurredOn
                    && $view->updatedAt == \DateTime::createFromImmutable($event->occurredOn)
                    && $view->email === $event->email
                    && $view->firstname === $event->firstname
                    && $view->lastname === $event->lastname
                    && $view->password === $event->password
                    && $view->consentGiven === $event->isConsentGiven
                    && $view->roles === $event->roles;
            }));
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserFirstnameUpdatedEvent(): void
    {
        $event = new UserFirstnameChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            UserId::fromString($event->aggregateId),
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
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLastnameUpdatedEvent(): void
    {
        $event = new UserLastnameChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'Doe',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            UserId::fromString($event->aggregateId),
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
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLanguagePreferenceUpdatedEvent(): void
    {
        $event = new UserLanguagePreferenceChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'fr',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            UserId::fromString($event->aggregateId),
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
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetEvent(): void
    {
        $event = new UserPasswordResetDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'newpassword123',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            UserId::fromString($event->aggregateId),
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
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetRequestedEvent(): void
    {
        $event = new UserPasswordResetRequestedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'reset-token-123',
            new \DateTimeImmutable('+1 hour'),
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            UserId::fromString($event->aggregateId),
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
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->mailer->expects($this->once())
            ->method('sendPasswordResetEmail')
            ->with($userView, 'reset-token-123');
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordUpdatedEvent(): void
    {
        $event = new UserPasswordChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'oldpassword123',
            'newpassword123',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            UserId::fromString($event->aggregateId),
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
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserDeletedEvent(): void
    {
        $event = new UserDeletedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );
        $userView = new UserView(
            UserId::fromString($event->aggregateId),
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
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('delete')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserFirstnameUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserFirstnameChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLastnameUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserLastnameChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'Doe',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserLanguagePreferenceUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserLanguagePreferenceChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'fr',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetEventWithUserThatDoesNotExist(): void
    {
        $event = new UserPasswordResetDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'newpassword123',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordResetRequestedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserPasswordResetRequestedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'reset-token-123',
            new \DateTimeImmutable('+1 hour'),
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserPasswordUpdatedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserPasswordChangedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'oldpassword123',
            'newpassword123',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserDeletedEventWithUserThatDoesNotExist(): void
    {
        $event = new UserDeletedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserReplayedEvent(): void
    {
        $event = new UserReplayedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            'password123',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $userView = new UserView(
            UserId::fromString($event->aggregateId),
            UserEmail::fromString($event->email),
            UserPassword::fromString($event->password),
            UserFirstname::fromString($event->firstname),
            UserLastname::fromString($event->lastname),
            UserLanguagePreference::fromString('fr'),
            UserConsent::fromBool($event->isConsentGiven),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserReplayedWithUserThatDoesNotExist(): void
    {
        $event = new UserReplayedDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            'password123',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserRewoundEvent(): void
    {
        $event = new UserRewoundDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            'password123',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $userView = new UserView(
            UserId::fromString($event->aggregateId),
            UserEmail::fromString($event->email),
            UserPassword::fromString($event->password),
            UserFirstname::fromString($event->firstname),
            UserLastname::fromString($event->lastname),
            UserLanguagePreference::fromString('fr'),
            UserConsent::fromBool($event->isConsentGiven),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTimeImmutable('2024-12-07T22:03:35+00:00'),
            new \DateTime('2024-12-07T22:03:35+00:00'),
            ['ROLE_USER'],
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn($userView);
        $this->userViewRepository->expects($this->once())
            ->method('save')
            ->with($userView);
        $this->publisher->expects($this->once())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }

    public function testHandleUserRewoundWithUserThatDoesNotExist(): void
    {
        $event = new UserRewoundDomainEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'John',
            'Doe',
            'fr',
            'john.doe@example.com',
            'password123',
            true,
            '2024-12-07T22:03:35+00:00',
            '2024-12-07T22:03:35+00:00',
            'b7e685be-db83-4866-9f85-102fac30a50b',
        );

        $this->keyManagementRepository->method('getKey')
            ->willReturn('encryption-key');
        $this->userViewRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['uuid' => $event->aggregateId])
            ->willReturn(null);
        $this->publisher->expects($this->never())
            ->method('publishNotificationEvents');

        $this->userProjection->__invoke($event);
    }
}
