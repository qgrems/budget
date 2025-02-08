<?php

namespace App\UserContext\Domain\Aggregates;

use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface;
use App\SharedContext\Domain\Traits\DomainEventsCapabilityTrait;
use App\UserContext\Domain\Events\UserDeletedDomainEvent;
use App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserLanguagePreferenceChangedDomainEvent;
use App\UserContext\Domain\Events\UserLastnameChangedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetDomainEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedDomainEvent;
use App\UserContext\Domain\Events\UserPasswordChangedDomainEvent;
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
use App\UserContext\Domain\ValueObjects\UserLanguagePreference;
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
    private UserLanguagePreference $languagePreference;
    private UserConsent $consentGiven;
    private \DateTimeImmutable $consentDate;
    private \DateTimeImmutable $createdAt;
    private \DateTime $updatedAt;
    private array $roles = ['ROLE_USER'];
    private int $aggregateVersion = 0;
    private ?UserPasswordResetToken $passwordResetToken;
    private ?\DateTimeImmutable $passwordResetTokenExpiry;

    private function __construct()
    {
    }

    public static function fromEvents(
        \Generator $events,
        EventEncryptorInterface $eventEncryptor,
        EventClassMapInterface $eventClassMap,
    ): self {
        $aggregate = new self();

        foreach ($events as $event) {
            $aggregate->apply(
                $eventClassMap->getEventPathByClassName($event['event_name'])::fromArray(
                    json_decode(
                        $event['payload'],
                        true,
                    )
                ),
                $eventEncryptor,
            );
            $aggregate->aggregateVersion = $event['stream_version'];
        }

        return $aggregate;
    }

    public static function create(
        UserId $userId,
        UserEmail $email,
        UserPassword $password,
        UserFirstname $firstname,
        UserLastname $lastname,
        UserLanguagePreference $languagePreference,
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
            (string) $languagePreference,
            $isConsentGiven->toBool(),
            $aggregate->roles,
            (string) $userId,
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

        $userFirstnameChangedDomainEvent = new UserFirstnameChangedDomainEvent(
            (string) $this->userId,
            (string) $firstname,
            (string) $this->userId,
        );

        $this->applyUserFirstnameChangedDomainEvent($userFirstnameChangedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userFirstnameChangedDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    public function updateLanguagePreference(
        UserLanguagePreference $languagePreference,
        UserId $userId,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $this->assertOwnership($userId);

        $userLanguagePreferenceChangedDomainEvent = new UserLanguagePreferenceChangedDomainEvent(
            (string) $this->userId,
            (string) $languagePreference,
            (string) $this->userId,
        );

        $this->applyUserLanguagePreferenceChangedDomainEvent($userLanguagePreferenceChangedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userLanguagePreferenceChangedDomainEvent,
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

        $userLastnameChangedDomainEvent = new UserLastnameChangedDomainEvent(
            (string) $this->userId,
            (string) $lastname,
            (string) $this->userId,
        );

        $this->applyUserLastnameChangedDomainEvent($userLastnameChangedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userLastnameChangedDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    public function delete(UserId $userId): void
    {
        $this->assertOwnership($userId);

        $userDeletedDomainEvent = new UserDeletedDomainEvent(
            (string) $this->userId,
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

        $userPasswordChangedDomainEvent = new UserPasswordChangedDomainEvent(
            (string) $this->userId,
            (string) $oldPassword,
            (string) $newPassword,
            (string) $this->userId,
        );

        $this->applyUserPasswordChangedDomainEvent($userPasswordChangedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userPasswordChangedDomainEvent,
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
            (string) $this->userId,
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
            (string) $this->userId,
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
            (string) $this->languagePreference,
            (string) $this->email,
            (string) $this->password,
            $this->consentGiven->toBool(),
            $this->consentDate->format(\DateTimeInterface::ATOM),
            $this->updatedAt->format(\DateTimeInterface::ATOM),
            (string) $this->userId,
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
            (string) $this->languagePreference,
            (string) $this->email,
            (string) $this->password,
            $this->consentGiven->toBool(),
            $this->consentDate->format(\DateTimeInterface::ATOM),
            $this->updatedAt->format(\DateTimeInterface::ATOM),
            (string) $this->userId,
        );
        $this->applyUserReplayedDomainEvent($userReplayedDomainEvent);
        $this->raiseDomainEvents(
            $eventEncryptor->encrypt(
                $userReplayedDomainEvent,
                (string) $this->userId,
            ),
        );
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    private function apply(
        UserDomainEventInterface $event,
        EventEncryptorInterface $eventEncryptor,
    ): void {
        $event = $eventEncryptor->decrypt($event, $event->aggregateId);

        match ($event::class) {
            UserSignedUpDomainEvent::class => $this->applyUserSignedUpDomainEvent($event),
            UserFirstnameChangedDomainEvent::class => $this->applyUserFirstnameChangedDomainEvent($event),
            UserLastnameChangedDomainEvent::class => $this->applyUserLastnameChangedDomainEvent($event),
            UserLanguagePreferenceChangedDomainEvent::class => $this->applyUserLanguagePreferenceChangedDomainEvent($event),
            UserPasswordChangedDomainEvent::class => $this->applyUserPasswordChangedDomainEvent($event),
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
        $this->languagePreference = UserLanguagePreference::fromString($userSignedUpDomainEvent->languagePreference);
        $this->updatedAt = \DateTime::createFromImmutable($userSignedUpDomainEvent->occurredOn);
        $this->createdAt = $userSignedUpDomainEvent->occurredOn;
        $this->consentGiven = UserConsent::fromBool($userSignedUpDomainEvent->isConsentGiven);
        $this->consentDate = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    private function applyUserFirstnameChangedDomainEvent(
        UserFirstnameChangedDomainEvent $userFirstnameChangedDomainEvent,
    ): void {
        $this->firstname = UserFirstname::fromString($userFirstnameChangedDomainEvent->firstname);
        $this->updatedAt = \DateTime::createFromImmutable($userFirstnameChangedDomainEvent->occurredOn);
    }

    private function applyUserLanguagePreferenceChangedDomainEvent(
        UserLanguagePreferenceChangedDomainEvent $userFirstnameChangedDomainEvent,
    ): void {
        $this->languagePreference = UserLanguagePreference::fromString($userFirstnameChangedDomainEvent->languagePreference);
        $this->updatedAt = \DateTime::createFromImmutable($userFirstnameChangedDomainEvent->occurredOn);
    }

    private function applyUserLastnameChangedDomainEvent(
        UserLastnameChangedDomainEvent $userLastnameChangedDomainEvent,
    ): void {
        $this->lastname = UserLastname::fromString($userLastnameChangedDomainEvent->lastname);
        $this->updatedAt = \DateTime::createFromImmutable($userLastnameChangedDomainEvent->occurredOn);
    }

    private function applyUserPasswordChangedDomainEvent(
        UserPasswordChangedDomainEvent $userPasswordChangedDomainEvent
    ): void {
        $this->password = UserPassword::fromString($userPasswordChangedDomainEvent->newPassword);
        $this->updatedAt = \DateTime::createFromImmutable($userPasswordChangedDomainEvent->occurredOn);
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
        $this->languagePreference = UserLanguagePreference::fromString($userReplayedDomainEvent->languagePreference);
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
        $this->languagePreference = UserLanguagePreference::fromString($userRewoundDomainEvent->languagePreference);
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
