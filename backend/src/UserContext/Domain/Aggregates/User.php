<?php

namespace App\UserContext\Domain\Aggregates;

use App\Libraries\FluxCapacitor\Anonymizer\Traits\UserDomainEventsCapabilityTrait;
use App\Libraries\FluxCapacitor\Anonymizer\Traits\EncryptedKeyCacheTrait;
use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\UserAggregateInterface;
use App\SharedContext\Domain\ValueObjects\UserLanguagePreference;
use App\SharedContext\Domain\ValueObjects\UtcClock;
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
use App\UserContext\Domain\Exceptions\InvalidUserOperationException;
use App\UserContext\Domain\Exceptions\UserIsNotOwnedByUserException;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;

final class User implements AggregateRootInterface, UserAggregateInterface
{
    use UserDomainEventsCapabilityTrait;
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

    public static function create(
        UserId $userId,
        UserEmail $email,
        UserPassword $password,
        UserFirstname $firstname,
        UserLastname $lastname,
        UserLanguagePreference $languagePreference,
        UserConsent $isConsentGiven,
    ): self {
        $aggregate = new self();
        $aggregate->raiseDomainEvents(
            new UserSignedUpDomainEvent(
                (string) $userId,
                (string) $email,
                (string) $password,
                (string) $firstname,
                (string) $lastname,
                (string) $languagePreference,
                $isConsentGiven->toBool(),
                $aggregate->roles,
                (string) $userId,
            ),
        );

        return $aggregate;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function updateFirstname(UserFirstname $firstname, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserFirstnameChangedDomainEvent(
                (string) $this->userId,
                (string) $firstname,
                (string) $this->userId,
            ),
        );
    }

    public function updateLanguagePreference(UserLanguagePreference $languagePreference, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserLanguagePreferenceChangedDomainEvent(
                (string) $this->userId,
                (string) $languagePreference,
                (string) $this->userId,
            ),
        );
    }

    public function updateLastname(UserLastname $lastname, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserLastnameChangedDomainEvent(
                (string) $this->userId,
                (string) $lastname,
                (string) $this->userId,
            ),
        );
    }

    public function delete(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserDeletedDomainEvent(
                (string) $this->userId,
                (string) $this->userId,
            ),
        );
    }

    public function updatePassword(UserPassword $oldPassword, UserPassword $newPassword, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserPasswordChangedDomainEvent(
                (string) $this->userId,
                (string) $oldPassword,
                (string) $newPassword,
                (string) $this->userId,
            ),
        );
    }

    public function setPasswordResetToken(UserPasswordResetToken $passwordResetToken, UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserPasswordResetRequestedDomainEvent(
                (string) $this->userId,
                (string) $passwordResetToken,
                new \DateTimeImmutable('+1 hour'),
                (string) $this->userId,
            ),
        );
    }

    public function resetPassword(UserPassword $password, UserId $userId): void
    {
        $this->assertOwnership($userId);

        if ($this->passwordResetTokenExpiry < UtcClock::now()) {
            throw InvalidUserOperationException::operationOnResetUserPassword();
        }

        $this->raiseDomainEvents(
            new UserPasswordResetDomainEvent(
                (string) $this->userId,
                (string) $password,
                (string) $this->userId,
            ),
        );
    }

    public function rewind(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserRewoundDomainEvent(
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
            ),
        );
    }

    public function replay(UserId $userId): void
    {
        $this->assertOwnership($userId);
        $this->raiseDomainEvents(
            new UserReplayedDomainEvent(
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
            ),
        );
    }

    public function aggregateVersion(): int
    {
        return $this->aggregateVersion;
    }

    public function setAggregateVersion(int $aggregateVersion): self
    {
        $this->aggregateVersion = $aggregateVersion;

        return $this;
    }

    public function getEmail(): UserEmail
    {
        return $this->email;
    }

    public function getAggregateId(): string
    {
        return (string) $this->userId;
    }

    public function applyUserSignedUpDomainEvent(UserSignedUpDomainEvent $userSignedUpDomainEvent): void
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
        $this->consentDate = UtcClock::now();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    public function applyUserFirstnameChangedDomainEvent(
        UserFirstnameChangedDomainEvent $userFirstnameChangedDomainEvent,
    ): void {
        $this->firstname = UserFirstname::fromString($userFirstnameChangedDomainEvent->firstname);
        $this->updatedAt = \DateTime::createFromImmutable($userFirstnameChangedDomainEvent->occurredOn);
    }

    public function applyUserLanguagePreferenceChangedDomainEvent(
        UserLanguagePreferenceChangedDomainEvent $userFirstnameChangedDomainEvent,
    ): void {
        $this->languagePreference = UserLanguagePreference::fromString($userFirstnameChangedDomainEvent->languagePreference);
        $this->updatedAt = \DateTime::createFromImmutable($userFirstnameChangedDomainEvent->occurredOn);
    }

    public function applyUserLastnameChangedDomainEvent(
        UserLastnameChangedDomainEvent $userLastnameChangedDomainEvent,
    ): void {
        $this->lastname = UserLastname::fromString($userLastnameChangedDomainEvent->lastname);
        $this->updatedAt = \DateTime::createFromImmutable($userLastnameChangedDomainEvent->occurredOn);
    }

    public function applyUserPasswordChangedDomainEvent(
        UserPasswordChangedDomainEvent $userPasswordChangedDomainEvent
    ): void {
        $this->password = UserPassword::fromString($userPasswordChangedDomainEvent->newPassword);
        $this->updatedAt = \DateTime::createFromImmutable($userPasswordChangedDomainEvent->occurredOn);
    }

    public function applyUserPasswordResetRequestedDomainEvent(
        UserPasswordResetRequestedDomainEvent $userPasswordResetRequestedDomainEvent,
    ): void {
        $this->passwordResetToken = UserPasswordResetToken::fromString(
            $userPasswordResetRequestedDomainEvent->passwordResetToken,
        );
        $this->passwordResetTokenExpiry = $userPasswordResetRequestedDomainEvent->passwordResetTokenExpiry;
        $this->updatedAt = \DateTime::createFromImmutable($userPasswordResetRequestedDomainEvent->occurredOn);
    }

    public function applyUserPasswordResetDomainEvent(UserPasswordResetDomainEvent $userPasswordResetDomainEvent): void
    {
        $this->password = UserPassword::fromString($userPasswordResetDomainEvent->password);
        $this->updatedAt = \DateTime::createFromImmutable($userPasswordResetDomainEvent->occurredOn);
    }

    public function applyUserDeletedDomainEvent(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function applyUserReplayedDomainEvent(UserReplayedDomainEvent $userReplayedDomainEvent): void
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

    public function applyUserRewoundDomainEvent(UserRewoundDomainEvent $userRewoundDomainEvent): void
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
