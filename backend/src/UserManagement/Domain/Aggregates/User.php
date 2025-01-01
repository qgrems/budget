<?php

namespace App\UserManagement\Domain\Aggregates;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\UserManagement\Domain\Events\UserSignedUpEvent;
use App\UserManagement\Domain\Events\UserDeletedEvent;
use App\UserManagement\Domain\Events\UserFirstnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserLastnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserPasswordResetEvent;
use App\UserManagement\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserManagement\Domain\Events\UserPasswordUpdatedEvent;
use App\UserManagement\Domain\Exceptions\InvalidUserOperationException;
use App\UserManagement\Domain\Exceptions\UserAlreadyExistsException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\ValueObjects\Consent;
use App\UserManagement\Domain\ValueObjects\Email;
use App\UserManagement\Domain\ValueObjects\Firstname;
use App\UserManagement\Domain\ValueObjects\PasswordResetToken;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\Lastname;
use App\UserManagement\Domain\ValueObjects\Password;

final class User
{
    private UserId $userId;

    private Email $email;

    private Password $password;

    private Firstname $firstname;

    private Lastname $lastname;

    private Consent $consentGiven;

    private \DateTimeImmutable $consentDate;

    private \DateTimeImmutable $createdAt;

    private \DateTime $updatedAt;

    private array $roles;

    private ?PasswordResetToken $passwordResetToken;

    private ?\DateTimeImmutable $passwordResetTokenExpiry;

    private array $uncommittedEvents = [];

    private function __construct()
    {
        $this->email = Email::create('init@mail.com');
        $this->password = Password::create('HAdFD97Xp[T!crjHi^Y%');
        $this->firstname = Firstname::create('init');
        $this->lastname = Lastname::create('init');
        $this->updatedAt = new \DateTime();
        $this->createdAt = new \DateTimeImmutable();
        $this->consentGiven = Consent::create(true);
        $this->consentDate = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    public static function reconstituteFromEvents(array $events): self
    {
        $aggregate = new self();

        foreach ($events as $event) {
            $aggregate->applyEvent($event['type']::fromArray(json_decode($event['payload'], true)));
        }

        return $aggregate;
    }

    public static function create(
        UserId $userId,
        Email $email,
        Password $password,
        Firstname $firstname,
        Lastname $lastname,
        Consent $isConsentGiven,
        UserViewRepositoryInterface $userViewRepository,
    ): self {
        if ($userViewRepository->findOneBy(['email' => (string) $email])) {
            throw new UserAlreadyExistsException(UserAlreadyExistsException::MESSAGE, 400);
        }

        $aggregate = new self();

        $event = new UserSignedUpEvent(
            (string) $userId,
            (string) $email,
            (string) $password,
            (string) $firstname,
            (string) $lastname,
            $isConsentGiven->toBool(),
            $aggregate->roles,
        );

        $aggregate->applyEvent($event);
        $aggregate->recordEvent($event);

        return $aggregate;
    }

    public function updateFirstname(Firstname $firstname, UserId $userId): void
    {
        $this->assertOwnership($userId);

        $event = new UserFirstnameUpdatedEvent(
            (string) $this->userId,
            (string) $firstname,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function updateLastname(Lastname $lastname, UserId $userId): void
    {
        $this->assertOwnership($userId);

        $event = new UserLastnameUpdatedEvent(
            (string) $this->userId,
            (string) $lastname,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function delete(UserId $userId): void
    {
        $this->assertOwnership($userId);

        $event = new UserDeletedEvent(
            (string) $this->userId,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function updatePassword(Password $oldPassword, Password $newPassword, UserId $userId): void
    {
        $this->assertOwnership($userId);

        $event = new UserPasswordUpdatedEvent(
            (string) $this->userId,
            (string) $oldPassword,
            (string) $newPassword,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function setPasswordResetToken(PasswordResetToken $passwordResetToken, UserId $userId): void
    {
        $this->assertOwnership($userId);

        $event = new UserPasswordResetRequestedEvent(
            (string) $this->userId,
            (string) $passwordResetToken,
            new \DateTimeImmutable('+1 hour'),
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function resetPassword(Password $password, UserId $userId): void
    {
        $this->assertOwnership($userId);

        if ($this->passwordResetTokenExpiry < new \DateTimeImmutable()) {
            throw InvalidUserOperationException::operationOnResetUserPassword();
        }

        $event = new UserPasswordResetEvent(
            (string) $this->userId,
            (string) $password,
        );

        $this->applyEvent($event);
        $this->recordEvent($event);
    }

    public function getUncommittedEvents(): array
    {
        return $this->uncommittedEvents;
    }

    public function clearUncommitedEvent(): void
    {
        $this->uncommittedEvents = [];
    }

    private function applyEvent(EventInterface $event): void
    {
        match (get_class($event)) {
            UserSignedUpEvent::class => $this->applyCreatedEvent($event),
            UserFirstnameUpdatedEvent::class => $this->applyFirstnameUpdated($event),
            UserLastnameUpdatedEvent::class => $this->applyLastnameUpdated($event),
            UserPasswordUpdatedEvent::class => $this->applyUserPasswordUpdated($event),
            UserPasswordResetRequestedEvent::class => $this->applyUserPasswordResetRequested($event),
            UserPasswordResetEvent::class => $this->applyUserPasswordReset($event),
            UserDeletedEvent::class => $this->applyUserDeleted(),
            default => throw new \RuntimeException('users.unknownEvent'),
        };
    }

    private function applyCreatedEvent(UserSignedUpEvent $event): void
    {
        $this->userId = UserId::create($event->getAggregateId());
        $this->email = Email::create($event->getEmail());
        $this->password = Password::create($event->getPassword());
        $this->firstname = Firstname::create($event->getFirstname());
        $this->lastname = Lastname::create($event->getLastname());
        $this->updatedAt = new \DateTime();
        $this->createdAt = new \DateTimeImmutable();
        $this->consentGiven = Consent::create($event->isConsentGiven());
        $this->consentDate = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    private function applyFirstnameUpdated(UserFirstnameUpdatedEvent $event): void
    {
        $this->firstname = Firstname::create($event->getFirstname());
        $this->updatedAt = new \DateTime();
    }

    private function applyLastnameUpdated(UserLastnameUpdatedEvent $event): void
    {
        $this->lastname = Lastname::create($event->getLastname());
        $this->updatedAt = new \DateTime();
    }

    private function applyUserPasswordUpdated(UserPasswordUpdatedEvent $event): void
    {
        $this->password = Password::create($event->getNewPassword());
        $this->updatedAt = new \DateTime();
    }

    private function applyUserPasswordResetRequested(UserPasswordResetRequestedEvent $event): void
    {
        $this->passwordResetToken = PasswordResetToken::create($event->getPasswordResetToken());
        $this->passwordResetTokenExpiry = $event->getPasswordResetTokenExpiry();
        $this->updatedAt = new \DateTime();
    }

    private function applyUserPasswordReset(UserPasswordResetEvent $event): void
    {
        $this->password = Password::create($event->getPassword());
        $this->updatedAt = new \DateTime();
    }

    private function applyUserDeleted(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function assertOwnership(UserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw new \RuntimeException('users.notOwner');
        }
    }

    private function recordEvent(EventInterface $event): void
    {
        $this->uncommittedEvents[] = $event;
    }
}
