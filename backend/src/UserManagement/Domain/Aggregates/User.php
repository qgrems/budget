<?php

namespace App\UserManagement\Domain\Aggregates;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\SharedContext\Traits\EventsCapability;
use App\UserManagement\Domain\Events\UserSignedUpEvent;
use App\UserManagement\Domain\Events\UserDeletedEvent;
use App\UserManagement\Domain\Events\UserFirstnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserLastnameUpdatedEvent;
use App\UserManagement\Domain\Events\UserPasswordResetEvent;
use App\UserManagement\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserManagement\Domain\Events\UserPasswordUpdatedEvent;
use App\UserManagement\Domain\Exceptions\InvalidUserOperationException;
use App\UserManagement\Domain\Exceptions\UserAlreadyExistsException;
use App\UserManagement\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserManagement\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserManagement\Domain\ValueObjects\UserConsent;
use App\UserManagement\Domain\ValueObjects\UserEmail;
use App\UserManagement\Domain\ValueObjects\UserFirstname;
use App\UserManagement\Domain\ValueObjects\UserPasswordResetToken;
use App\UserManagement\Domain\ValueObjects\UserId;
use App\UserManagement\Domain\ValueObjects\UserLastname;
use App\UserManagement\Domain\ValueObjects\UserPassword;

final class User
{
    use EventsCapability;

    private UserId $userId;
    private UserEmail $email;
    private UserPassword $password;
    private UserFirstname $firstname;
    private UserLastname $lastname;
    private UserConsent $consentGiven;
    private \DateTimeImmutable $consentDate;
    private \DateTimeImmutable $createdAt;
    private \DateTime $updatedAt;
    private array $roles = ['ROLE_USER'];
    private ?UserPasswordResetToken $passwordResetToken;
    private ?\DateTimeImmutable $passwordResetTokenExpiry;

    private function __construct()
    {
    }

    public static function fromEvents(\Generator $events): self
    {
        $aggregate = new self();

        foreach ($events as $event) {
            $aggregate->apply($event['type']::fromArray(json_decode($event['payload'], true)));
        }

        return $aggregate;
    }

    public static function create(
        UserId $userId,
        UserEmail $email,
        UserPassword $password,
        UserFirstname $firstname,
        UserLastname $lastname,
        UserConsent $isConsentGiven,
        UserViewRepositoryInterface $userViewRepository,
    ): self {
        if ($userViewRepository->findOneBy(['email' => (string) $email])) {
            throw new UserAlreadyExistsException();
        }

        $aggregate = new self();

        $userSignedUpEvent = new UserSignedUpEvent(
            (string) $userId,
            (string) $email,
            (string) $password,
            (string) $firstname,
            (string) $lastname,
            $isConsentGiven->toBool(),
            $aggregate->roles,
        );

        $aggregate->apply($userSignedUpEvent);
        $aggregate->raise($userSignedUpEvent);

        return $aggregate;
    }

    public function updateFirstname(
        UserFirstname $firstname,
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);

        $userFirstnameUpdatedEvent = new UserFirstnameUpdatedEvent(
            (string) $this->userId,
            (string) $firstname,
        );

        $this->apply($userFirstnameUpdatedEvent);
        $this->raise($userFirstnameUpdatedEvent);
    }

    public function updateLastname(
        UserLastname $lastname,
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);

        $userLastnameUpdatedEvent = new UserLastnameUpdatedEvent(
            (string) $this->userId,
            (string) $lastname,
        );

        $this->apply($userLastnameUpdatedEvent);
        $this->raise($userLastnameUpdatedEvent);
    }

    public function delete(UserId $userId): void
    {
        $this->assertOwnership($userId);

        $userDeletedEvent = new UserDeletedEvent(
            (string) $this->userId,
        );

        $this->apply($userDeletedEvent);
        $this->raise($userDeletedEvent);
    }

    public function updatePassword(
        UserPassword $oldPassword,
        UserPassword $newPassword,
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);

        $userPasswordUpdatedEvent = new UserPasswordUpdatedEvent(
            (string) $this->userId,
            (string) $oldPassword,
            (string) $newPassword,
        );

        $this->apply($userPasswordUpdatedEvent);
        $this->raise($userPasswordUpdatedEvent);
    }

    public function setPasswordResetToken(
        UserPasswordResetToken $passwordResetToken,
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);

        $userPasswordResetRequestedEvent = new UserPasswordResetRequestedEvent(
            (string) $this->userId,
            (string) $passwordResetToken,
            new \DateTimeImmutable('+1 hour'),
        );

        $this->apply($userPasswordResetRequestedEvent);
        $this->raise($userPasswordResetRequestedEvent);
    }

    public function resetPassword(
        UserPassword $password,
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);

        if ($this->passwordResetTokenExpiry < new \DateTimeImmutable()) {
            throw InvalidUserOperationException::operationOnResetUserPassword();
        }

        $userPasswordResetEvent = new UserPasswordResetEvent(
            (string) $this->userId,
            (string) $password,
        );

        $this->apply($userPasswordResetEvent);
        $this->raise($userPasswordResetEvent);
    }

    private function apply(EventInterface $event): void
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
        $this->userId = UserId::fromString($event->getAggregateId());
        $this->email = UserEmail::fromString($event->getEmail());
        $this->password = UserPassword::fromString($event->getPassword());
        $this->firstname = UserFirstname::fromString($event->getFirstname());
        $this->lastname = UserLastname::fromString($event->getLastname());
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
        $this->createdAt = $event->occurredOn();
        $this->consentGiven = UserConsent::fromBool($event->isConsentGiven());
        $this->consentDate = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    private function applyFirstnameUpdated(UserFirstnameUpdatedEvent $event): void
    {
        $this->firstname = UserFirstname::fromString($event->getFirstname());
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyLastnameUpdated(UserLastnameUpdatedEvent $event): void
    {
        $this->lastname = UserLastname::fromString($event->getLastname());
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyUserPasswordUpdated(UserPasswordUpdatedEvent $event): void
    {
        $this->password = UserPassword::fromString($event->getNewPassword());
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyUserPasswordResetRequested(UserPasswordResetRequestedEvent $event): void
    {
        $this->passwordResetToken = UserPasswordResetToken::fromString($event->getPasswordResetToken());
        $this->passwordResetTokenExpiry = $event->getPasswordResetTokenExpiry();
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyUserPasswordReset(UserPasswordResetEvent $event): void
    {
        $this->password = UserPassword::fromString($event->getPassword());
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn());
    }

    private function applyUserDeleted(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function assertOwnership(UserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw new UserIsNotOwnedByUserException('users.notOwner');
        }
    }
}
