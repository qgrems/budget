<?php

namespace App\UserContext\Domain\Aggregates;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\SharedContext\Domain\Traits\EventsCapabilityTrait;
use App\UserContext\Domain\Events\UserDeletedEvent;
use App\UserContext\Domain\Events\UserFirstnameUpdatedEvent;
use App\UserContext\Domain\Events\UserLastnameUpdatedEvent;
use App\UserContext\Domain\Events\UserPasswordResetEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserContext\Domain\Events\UserPasswordUpdatedEvent;
use App\UserContext\Domain\Events\UserReplayedEvent;
use App\UserContext\Domain\Events\UserRewoundEvent;
use App\UserContext\Domain\Events\UserSignedUpEvent;
use App\UserContext\Domain\Exceptions\InvalidUserOperationException;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;

final class User
{
    use EventsCapabilityTrait;

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

    public function rewind(
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);
        $userRewoundEvent = new UserRewoundEvent(
            (string) $this->userId,
            (string) $this->firstname,
            (string) $this->lastname,
            (string) $this->email,
            (string) $this->password,
            $this->consentGiven->toBool(),
            $this->consentDate->format(\DateTimeInterface::ATOM),
            $this->updatedAt->format(\DateTimeInterface::ATOM),
        );
        $this->apply($userRewoundEvent);
        $this->raise($userRewoundEvent);
    }

    public function replay(
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);
        $userReplayedEvent = new UserReplayedEvent(
            (string) $this->userId,
            (string) $this->firstname,
            (string) $this->lastname,
            (string) $this->email,
            (string) $this->password,
            $this->consentGiven->toBool(),
            $this->consentDate->format(\DateTimeInterface::ATOM),
            $this->updatedAt->format(\DateTimeInterface::ATOM),
        );
        $this->raise($userReplayedEvent);
        $this->apply($userReplayedEvent);
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
            UserReplayedEvent::class => $this->applyReplayedEvent($event),
            UserRewoundEvent::class => $this->applyRewoundEvent($event),
            default => throw new \RuntimeException('users.unknownEvent'),
        };
    }

    private function applyCreatedEvent(UserSignedUpEvent $event): void
    {
        $this->userId = UserId::fromString($event->aggregateId);
        $this->email = UserEmail::fromString($event->email);
        $this->password = UserPassword::fromString($event->password);
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->createdAt = $event->occurredOn;
        $this->consentGiven = UserConsent::fromBool($event->isConsentGiven);
        $this->consentDate = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    private function applyFirstnameUpdated(UserFirstnameUpdatedEvent $event): void
    {
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyLastnameUpdated(UserLastnameUpdatedEvent $event): void
    {
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordUpdated(UserPasswordUpdatedEvent $event): void
    {
        $this->password = UserPassword::fromString($event->newPassword);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordResetRequested(UserPasswordResetRequestedEvent $event): void
    {
        $this->passwordResetToken = UserPasswordResetToken::fromString($event->passwordResetToken);
        $this->passwordResetTokenExpiry = $event->passwordResetTokenExpiry;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordReset(UserPasswordResetEvent $event): void
    {
        $this->password = UserPassword::fromString($event->password);
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserDeleted(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function applyReplayedEvent(UserReplayedEvent $event): void
    {
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->email = UserEmail::fromString($event->email);
        $this->password = UserPassword::fromString($event->password);
        $this->consentGiven = UserConsent::fromBool($event->isConsentGiven);
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyRewoundEvent(UserRewoundEvent $event): void
    {
        $this->firstname = UserFirstname::fromString($event->firstname);
        $this->lastname = UserLastname::fromString($event->lastname);
        $this->email = UserEmail::fromString($event->email);
        $this->password = UserPassword::fromString($event->password);
        $this->consentGiven = UserConsent::fromBool($event->isConsentGiven);
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
    }

    private function assertOwnership(UserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw new UserIsNotOwnedByUserException('users.notOwner');
        }
    }
}
