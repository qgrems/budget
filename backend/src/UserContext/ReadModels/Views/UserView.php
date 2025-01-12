<?php

declare(strict_types=1);

namespace App\UserContext\ReadModels\Views;

use App\SharedContext\Domain\Ports\Inbound\EventInterface;
use App\SharedContext\Domain\Ports\Inbound\SharedUserInterface;
use App\UserContext\Domain\Events\UserFirstnameUpdatedEvent;
use App\UserContext\Domain\Events\UserLastnameUpdatedEvent;
use App\UserContext\Domain\Events\UserPasswordResetEvent;
use App\UserContext\Domain\Events\UserPasswordResetRequestedEvent;
use App\UserContext\Domain\Events\UserPasswordUpdatedEvent;
use App\UserContext\Domain\Events\UserReplayedEvent;
use App\UserContext\Domain\Events\UserRewoundEvent;
use App\UserContext\Domain\Events\UserSignedUpEvent;
use App\UserContext\Domain\Ports\Inbound\UserViewInterface;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_view')]
final class UserView implements UserViewInterface, UserInterface, PasswordAuthenticatedUserInterface, SharedUserInterface, \JsonSerializable
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private(set) int $id;

    #[ORM\Column(name: 'uuid', type: 'string', length: 36, unique: true)]
    private(set) string $uuid;

    #[ORM\Column(name: 'email', type: 'string', length: 320)]
    private(set) string $email;

    #[ORM\Column(name: 'password', type: 'string', length: 255)]
    private(set) string $password;

    #[ORM\Column(name: 'firstname', type: 'string', length: 50)]
    private(set) string $firstname;

    #[ORM\Column(name: 'lastname', type: 'string', length: 50)]
    private(set) string $lastname;

    #[ORM\Column(name: 'consent_given', type: 'boolean')]
    private(set) bool $consentGiven;

    #[ORM\Column(name: 'consent_date', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $consentDate;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime')]
    private(set) \DateTime $updatedAt;

    /**
     * @var array<string> $roles
     */
    #[ORM\Column(name: 'roles', type: 'json')]
    private(set) array $roles = ['ROLE_USER'];

    #[ORM\Column(name: 'password_reset_token', type: 'string', length: 64, nullable: true)]
    private(set) ?string $passwordResetToken = null;

    #[ORM\Column(name: 'password_reset_token_expiry', type: 'datetime_immutable', nullable: true)]
    private(set) ?\DateTimeImmutable $passwordResetTokenExpiry = null;

    public function __construct(
        UserId $userId,
        UserEmail $email,
        UserPassword $password,
        UserFirstname $firstname,
        UserLastname $lastname,
        UserConsent $consentGiven,
        \DateTimeImmutable $consentDate,
        \DateTimeImmutable $createdAt,
        \DateTime $updatedAt,
        array $roles,
        ?UserPasswordResetToken $passwordResetToken = null,
        ?\DateTimeImmutable $passwordResetTokenExpiry = null,

    ) {
        $this->uuid = (string) $userId;
        $this->email = (string) $email;
        $this->password = (string) $password;
        $this->firstname = (string) $firstname;
        $this->lastname = (string) $lastname;
        $this->consentGiven = $consentGiven->toBool();
        $this->consentDate = $consentDate;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->roles = $roles;
        $this->passwordResetToken = (string) $passwordResetToken ?? null;
        $this->passwordResetTokenExpiry = $passwordResetTokenExpiry;
    }

    public static function fromRepository(array $user): self
    {
        return new self(
            UserId::fromString($user['uuid']),
            UserEmail::fromString($user['email']),
            UserPassword::fromString($user['password']),
            UserFirstname::fromString($user['firstname']),
            UserLastname::fromString($user['lastname']),
            UserConsent::fromBool((bool) $user['consent_given']),
            new \DateTimeImmutable($user['consent_date']),
            new \DateTimeImmutable($user['created_at']),
            new \DateTime($user['updated_at']),
            json_decode($user['roles'], true),
            UserPasswordResetToken::fromString($user['password_reset_token']) ?? null,
            $user['password_reset_token_expiry'] ?
                new \DateTimeImmutable($user['password_reset_token_expiry']) :
                null,
        );
    }

    public static function fromUserSignedUpEvent(UserSignedUpEvent $userSignedUpEvent): self
    {
        return new self(
            UserId::fromString($userSignedUpEvent->aggregateId),
            UserEmail::fromString($userSignedUpEvent->email),
            UserPassword::fromString($userSignedUpEvent->password),
            UserFirstname::fromString($userSignedUpEvent->firstname),
            UserLastname::fromString($userSignedUpEvent->lastname),
            UserConsent::fromBool($userSignedUpEvent->isConsentGiven),
            $userSignedUpEvent->occurredOn,
            $userSignedUpEvent->occurredOn,
            \DateTime::createFromImmutable($userSignedUpEvent->occurredOn),
            $userSignedUpEvent->roles,
        );
    }

    public function fromEvents(\Generator $events): void
    {
        foreach ($events as $event) {
            $this->apply($event['type']::fromArray(json_decode($event['payload'], true)));
        }
    }

    public function fromEvent(EventInterface $event): void
    {
        $this->apply($event);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'email' => $this->email,
        ];
    }

    private function apply(EventInterface $event): void
    {
        match (get_class($event)) {
            UserSignedUpEvent::class => $this->applyUserCreatedEvent($event),
            UserFirstnameUpdatedEvent::class => $this->applyFirstnameUpdated($event),
            UserLastnameUpdatedEvent::class => $this->applyLastnameUpdated($event),
            UserPasswordUpdatedEvent::class => $this->applyUserPasswordUpdated($event),
            UserPasswordResetRequestedEvent::class => $this->applyUserPasswordResetRequested($event),
            UserPasswordResetEvent::class => $this->applyUserPasswordReset($event),
            UserReplayedEvent::class => $this->applyUserReplayedEvent($event),
            UserRewoundEvent::class => $this->applyUserRewoundEvent($event),
            default => throw new \RuntimeException('users.unknownEvent'),
        };
    }

    private function applyUserCreatedEvent(UserSignedUpEvent $event): void
    {
        $this->uuid = $event->aggregateId;
        $this->email = $event->email;
        $this->password = $event->password;
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
        $this->createdAt = $event->occurredOn;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = new \DateTimeImmutable();
        $this->roles = ['ROLE_USER'];
        $this->passwordResetToken = null;
        $this->passwordResetTokenExpiry = null;
    }

    private function applyFirstnameUpdated(UserFirstnameUpdatedEvent $event): void
    {
        $this->firstname = $event->firstname;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyLastnameUpdated(UserLastnameUpdatedEvent $event): void
    {
        $this->lastname = $event->lastname;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordUpdated(UserPasswordUpdatedEvent $event): void
    {
        $this->password = $event->newPassword;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordResetRequested(UserPasswordResetRequestedEvent $event): void
    {
        $this->passwordResetToken = $event->passwordResetToken;
        $this->passwordResetTokenExpiry = $event->passwordResetTokenExpiry;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserPasswordReset(UserPasswordResetEvent $event): void
    {
        $this->password = $event->password;
        $this->updatedAt = \DateTime::createFromImmutable($event->occurredOn);
    }

    private function applyUserReplayedEvent(UserReplayedEvent $event): void
    {
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->email = $event->email;
        $this->password = $event->password;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
    }

    private function applyUserRewoundEvent(UserRewoundEvent $event): void
    {
        $this->firstname = $event->firstname;
        $this->lastname = $event->lastname;
        $this->email = $event->email;
        $this->password = $event->password;
        $this->consentGiven = $event->isConsentGiven;
        $this->consentDate = $event->consentDate;
        $this->updatedAt = $event->updatedAt;
    }
}
