<?php

namespace App\UserContext\Domain\Aggregates;

use App\SharedContext\Domain\Traits\DomainEventsCapabilityTrait;
use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameUpdatedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameUpdatedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordUpdatedDomainEvent;
use App\UserContext\Domain\Events\UserReplayedDomainEvent;
use App\UserContext\Domain\Events\UserRewoundDomainEvent;
use App\UserContext\Domain\Events\UserSignedUpDomainEvent;
use App\UserContext\Domain\Exceptions\InvalidUserOperationException;
use App\UserContext\Domain\Exceptions\UserAlreadyExistsException;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;
use App\UserContext\Domain\Ports\Inbound\UserViewRepositoryInterface;
use App\UserContext\Domain\Traits\EncryptedKeyCacheTrait;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;

final class User
{
    use DomainEventsCapabilityTrait;
    use EncryptedKeyCacheTrait;

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

    public static function fromEvents(
        \Generator $events,
        EventEncryptorInterface $eventEncryptor,
    ): self {
        $aggregate = new self();

        foreach ($events as $event) {
            $aggregate->apply(
                $event['type']::fromArray(json_decode($event['payload'], true)),
                $eventEncryptor,
            );
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
        EventEncryptorInterface $eventEncryptor,
    ): self {
        if ($userViewRepository->findOneBy(['email' => (string) $email])) {
            throw new UserAlreadyExistsException();
        }

        $aggregate = new self();

        $userSignedUpEvent = new UserSignedUpDomainEvent(
            (string) $userId,
            (string) $email,
            (string) $password,
            (string) $firstname,
            (string) $lastname,
            $isConsentGiven->toBool(),
            $aggregate->roles,
        );

        $aggregate->applyUserSignedUpDomainEvent($userSignedUpEvent);
        $aggregate->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userSignedUpEvent,
                (string) $userId,
            ),
        );

        return $aggregate;
    }

    public function updateFirstname(
        UserFirstname $firstname,
        UserId $userId,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $this->assertOwnership($userId);

        $userFirstnameUpdatedDomainEvent = new UserFirstnameUpdatedDomainEvent(
            (string) $this->userId,
            (string) $firstname,
        );

        $this->applyUserFirstnameUpdatedDomainEvent($userFirstnameUpdatedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userFirstnameUpdatedDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    public function updateLastname(
        UserLastname $lastname,
        UserId $userId,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $this->assertOwnership($userId);

        $userLastnameUpdatedDomainEvent = new UserLastnameUpdatedDomainEvent(
            (string) $this->userId,
            (string) $lastname,
        );

        $this->applyUserLastnameUpdatedDomainEvent($userLastnameUpdatedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userLastnameUpdatedDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    public function delete(UserId $userId): void
    {
        $this->assertOwnership($userId);

        $userDeletedDomainEvent = new UserDeletedDomainEvent(
            (string) $this->userId,
        );

        $this->applyUserDeletedDomainEvent();
        $this->raiseDomainEvents($userDeletedDomainEvent);
    }

    public function updatePassword(
        UserPassword $oldPassword,
        UserPassword $newPassword,
        UserId $userId,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $this->assertOwnership($userId);

        $userPasswordUpdatedDomainEvent = new UserPasswordUpdatedDomainEvent(
            (string) $this->userId,
            (string) $oldPassword,
            (string) $newPassword,
        );

        $this->applyUserPasswordUpdatedDomainEvent($userPasswordUpdatedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userPasswordUpdatedDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    public function setPasswordResetToken(
        UserPasswordResetToken $passwordResetToken,
        UserId $userId,
    ): void {
        $this->assertOwnership($userId);

        $userPasswordResetRequestedDomainEvent = new UserPasswordResetRequestedDomainEvent(
            (string) $this->userId,
            (string) $passwordResetToken,
            new \DateTimeImmutable('+1 hour'),
        );

        $this->applyUserPasswordResetRequestedDomainEvent($userPasswordResetRequestedDomainEvent);
        $this->raiseDomainEvents($userPasswordResetRequestedDomainEvent);
    }

    public function resetPassword(
        UserPassword $password,
        UserId $userId,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $this->assertOwnership($userId);

        if ($this->passwordResetTokenExpiry < new \DateTimeImmutable()) {
            throw InvalidUserOperationException::operationOnResetUserPassword();
        }

        $userPasswordResetEvent = new UserPasswordResetDomainEvent(
            (string) $this->userId,
            (string) $password,
        );

        $this->applyUserPasswordResetDomainEvent($userPasswordResetEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userPasswordResetEvent,
                (string) $this->userId,
            ),
        );
    }

    public function rewind(
        UserId $userId,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $this->assertOwnership($userId);
        $userRewoundDomainEvent = new UserRewoundDomainEvent(
            (string) $this->userId,
            (string) $this->firstname,
            (string) $this->lastname,
            (string) $this->email,
            (string) $this->password,
            $this->consentGiven->toBool(),
            $this->consentDate->format(\DateTimeInterface::ATOM),
            $this->updatedAt->format(\DateTimeInterface::ATOM),
        );
        $this->applyUserRewoundDomainEvent($userRewoundDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userRewoundDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    public function replay(
        UserId $userId,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $this->assertOwnership($userId);
        $userReplayedDomainEvent = new UserReplayedDomainEvent(
            (string) $this->userId,
            (string) $this->firstname,
            (string) $this->lastname,
            (string) $this->email,
            (string) $this->password,
            $this->consentGiven->toBool(),
            $this->consentDate->format(\DateTimeInterface::ATOM),
            $this->updatedAt->format(\DateTimeInterface::ATOM),
        );
        $this->applyUserReplayedDomainEvent($userReplayedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userReplayedDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    private function apply(
        UserDomainEventInterface $event,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $event = $eventEncryptor->decrypt($event, $event->aggregateId);

        match ($event::class) {
            UserSignedUpDomainEvent::class => $this->applyUserSignedUpDomainEvent($event),
            UserFirstnameUpdatedDomainEvent::class => $this->applyUserFirstnameUpdatedDomainEvent($event),
            UserLastnameUpdatedDomainEvent::class => $this->applyUserLastnameUpdatedDomainEvent($event),
            UserPasswordUpdatedDomainEvent::class => $this->applyUserPasswordUpdatedDomainEvent($event),
            UserPasswordResetRequestedDomainEvent::class => $this->applyUserPasswordResetRequestedDomainEvent($event),
            UserPasswordResetDomainEvent::class => $this->applyUserPasswordResetDomainEvent($event),
            UserDeletedDomainEvent::class => $this->applyUserDeletedDomainEvent(),
            UserReplayedDomainEvent::class => $this->applyUserReplayedDomainEvent($event),
            UserRewoundDomainEvent::class => $this->applyUserRewoundDomainEvent($event),
            default => throw new \RuntimeException('users.unknownEvent'),
        };
    }

    private function applyUserSignedUpDomainEvent(UserSignedUpDomainEvent $userSignedUpDomainEvent): void
    {
        $this->userId = UserId::fromString($userSignedUpDomainEvent->aggregateId);
        $this->email = UserEmail::fromString($userSignedUpDomainEvent->email);
        $this->password = UserPassword::fromString($userSignedUpDomainEvent->password);
        $this->firstname = UserFirstname::fromString($userSignedUpDomainEvent->firstname);
        $this->lastname = UserLastname::fromString($userSignedUpDomainEvent->lastname);
        $this->updatedAt = \DateTime::createFromImmutable($userSignedUpDomainEvent->occurredOn);
        $this->createdAt = $userSignedUpDomainEvent->occurredOn;
        $this->consentGiven = UserConsent::fromBool($userSignedUpDomainEvent->isConsentGiven);
        $this->consentDate = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    private function applyUserFirstnameUpdatedDomainEvent(
        UserFirstnameUpdatedDomainEvent $userFirstnameUpdatedDomainEvent,
    ): void {
        $this->firstname = UserFirstname::fromString($userFirstnameUpdatedDomainEvent->firstname);
        $this->updatedAt = \DateTime::createFromImmutable($userFirstnameUpdatedDomainEvent->occurredOn);
    }

    private function applyUserLastnameUpdatedDomainEvent(
        UserLastnameUpdatedDomainEvent $userLastnameUpdatedDomainEvent,
    ): void {
        $this->lastname = UserLastname::fromString($userLastnameUpdatedDomainEvent->lastname);
        $this->updatedAt = \DateTime::createFromImmutable($userLastnameUpdatedDomainEvent->occurredOn);
    }

    private function applyUserPasswordUpdatedDomainEvent(
        UserPasswordUpdatedDomainEvent $userPasswordUpdatedDomainEvent
    ): void {
        $this->password = UserPassword::fromString($userPasswordUpdatedDomainEvent->newPassword);
        $this->updatedAt = \DateTime::createFromImmutable($userPasswordUpdatedDomainEvent->occurredOn);
    }

    private function applyUserPasswordResetRequestedDomainEvent(
        UserPasswordResetRequestedDomainEvent $userPasswordResetRequestedDomainEvent,
    ): void {
        $this->passwordResetToken = UserPasswordResetToken::fromString(
            $userPasswordResetRequestedDomainEvent->passwordResetToken,
        );
        $this->passwordResetTokenExpiry = $userPasswordResetRequestedDomainEvent->passwordResetTokenExpiry;
        $this->updatedAt = \DateTime::createFromImmutable($userPasswordResetRequestedDomainEvent->occurredOn);
    }

    private function applyUserPasswordResetDomainEvent(UserPasswordResetDomainEvent $userPasswordResetDomainEvent): void
    {
        $this->password = UserPassword::fromString($userPasswordResetDomainEvent->password);
        $this->updatedAt = \DateTime::createFromImmutable($userPasswordResetDomainEvent->occurredOn);
    }

    private function applyUserDeletedDomainEvent(): void
    {
        $this->updatedAt = new \DateTime();
    }

    private function applyUserReplayedDomainEvent(UserReplayedDomainEvent $userReplayedDomainEvent): void
    {
        $this->firstname = UserFirstname::fromString($userReplayedDomainEvent->firstname);
        $this->lastname = UserLastname::fromString($userReplayedDomainEvent->lastname);
        $this->email = UserEmail::fromString($userReplayedDomainEvent->email);
        $this->password = UserPassword::fromString($userReplayedDomainEvent->password);
        $this->consentGiven = UserConsent::fromBool($userReplayedDomainEvent->isConsentGiven);
        $this->consentDate = $userReplayedDomainEvent->consentDate;
        $this->updatedAt = $userReplayedDomainEvent->updatedAt;
    }

    private function applyUserRewoundDomainEvent(UserRewoundDomainEvent $userRewoundDomainEvent): void
    {
        $this->firstname = UserFirstname::fromString($userRewoundDomainEvent->firstname);
        $this->lastname = UserLastname::fromString($userRewoundDomainEvent->lastname);
        $this->email = UserEmail::fromString($userRewoundDomainEvent->email);
        $this->password = UserPassword::fromString($userRewoundDomainEvent->password);
        $this->consentGiven = UserConsent::fromBool($userRewoundDomainEvent->isConsentGiven);
        $this->consentDate = $userRewoundDomainEvent->consentDate;
        $this->updatedAt = $userRewoundDomainEvent->updatedAt;
    }

    private function assertOwnership(UserId $userId): void
    {
        if (!$this->userId->equals($userId)) {
            throw new UserIsNotOwnedByUserException('users.notOwner');
        }
    }
}
