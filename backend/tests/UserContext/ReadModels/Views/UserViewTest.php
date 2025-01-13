<?php

declare(strict_types=1);

namespace App\Tests\UserContext\ReadModels\Views;

use App\UserContext\Domain\Events\UserSignedUpEvent;
use App\UserContext\Domain\ValueObjects\UserConsent;
use App\UserContext\Domain\ValueObjects\UserEmail;
use App\UserContext\Domain\ValueObjects\UserFirstname;
use App\UserContext\Domain\ValueObjects\UserId;
use App\UserContext\Domain\ValueObjects\UserLastname;
use App\UserContext\Domain\ValueObjects\UserPassword;
use App\UserContext\Domain\ValueObjects\UserPasswordResetToken;
use App\UserContext\ReadModels\Views\UserView;
use PHPUnit\Framework\TestCase;

class UserViewTest extends TestCase
{
    public function testUserView(): void
    {
        $userView = new UserView(
            UserId::fromString('b7e685be-db83-4866-9f85-102fac30a50b'),
            UserEmail::fromString('john.doe@example.com'),
            UserPassword::fromString('password123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTime('2023-01-01T00:00:00+00:00'),
            ['ROLE_USER'],
            UserPasswordResetToken::fromString('reset-token-123'),
            new \DateTimeImmutable('+1 hour')
        );

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $userView->uuid);
        $this->assertEquals('john.doe@example.com', $userView->getEmail());
        $this->assertEquals('password123', $userView->getPassword());
        $this->assertEquals('John', $userView->firstname);
        $this->assertEquals('Doe', $userView->lastname);
        $this->assertTrue($userView->consentGiven);
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->consentDate->format(\DateTimeInterface::ATOM));
        $this->assertEquals(['ROLE_USER'], $userView->getRoles());
        $this->assertEquals('reset-token-123', $userView->passwordResetToken);
        $this->assertEquals((new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM), $userView->passwordResetTokenExpiry->format(\DateTimeInterface::ATOM));
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->createdAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->updatedAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals('john.doe@example.com', $userView->getUserIdentifier());
        $this->assertEquals(null, $userView->eraseCredentials());
    }

    public function testJsonSerialize(): void
    {
        $userView = new UserView(
            UserId::fromString('b7e685be-db83-4866-9f85-102fac30a50b'),
            UserEmail::fromString('john.doe@example.com'),
            UserPassword::fromString('password123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTime('2023-01-01T00:00:00+00:00'),
            ['ROLE_USER'],
            UserPasswordResetToken::fromString('reset-token-123'),
            new \DateTimeImmutable('+1 hour')
        );

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
        ];

        $this->assertEquals($expected, $userView->jsonSerialize());
    }

    public function testCreateFromRepository(): void
    {
        $userData = [
            'id' => 1,
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'email' => 'john.doe@example.com',
            'password' => 'password123',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'consent_given' => true,
            'consent_date' => '2023-01-01T00:00:00+00:00',
            'created_at' => '2023-01-01T00:00:00+00:00',
            'updated_at' => '2023-01-01T00:00:00+00:00',
            'roles' => json_encode(['ROLE_USER']),
            'password_reset_token' => 'reset-token-123',
            'password_reset_token_expiry' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
        ];

        $userView = UserView::fromRepository($userData);

        $this->assertEquals($userData['uuid'], $userView->getUuid());
        $this->assertEquals($userData['email'], $userView->getEmail());
        $this->assertEquals($userData['password'], $userView->getPassword());
        $this->assertEquals($userData['firstname'], $userView->firstname);
        $this->assertEquals($userData['lastname'], $userView->lastname);
        $this->assertEquals($userData['consent_given'], $userView->consentGiven);
        $this->assertEquals($userData['consent_date'], $userView->consentDate->format(\DateTimeInterface::ATOM));
        $this->assertEquals($userData['created_at'], $userView->createdAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals($userData['updated_at'], $userView->updatedAt->format(\DateTimeInterface::ATOM));
        $this->assertEquals(json_decode($userData['roles'], true), $userView->getRoles());
        $this->assertEquals($userData['password_reset_token'], $userView->passwordResetToken);
        $this->assertEquals($userData['password_reset_token_expiry'], $userView->passwordResetTokenExpiry->format(\DateTimeInterface::ATOM));
    }

    public function testFromEvents(): void
    {
        $userView = new UserView(
            UserId::fromString('b7e685be-db83-4866-9f85-102fac30a50b'),
            UserEmail::fromString('john.doe@example.com'),
            UserPassword::fromString('password123'),
            UserFirstname::fromString('John'),
            UserLastname::fromString('Doe'),
            UserConsent::fromBool(true),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTimeImmutable('2023-01-01T00:00:00+00:00'),
            new \DateTime('2023-01-01T00:00:00+00:00'),
            ['ROLE_USER'],
            UserPasswordResetToken::fromString('reset-token-123'),
            new \DateTimeImmutable('+1 hour')
        );

        $userView->fromEvents(
            (function () {
                yield [
                    'type' => UserSignedUpEvent::class,
                    'payload' => json_encode([
                        'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
                        'email' => 'john.doe@example.com',
                        'password' => 'password123',
                        'firstname' => 'John',
                        'lastname' => 'Doe',
                        'isConsentGiven' => true,
                        'occurredOn' => '2023-01-01T00:00:00+00:00',
                        'roles' => ['ROLE_USER'],
                    ]),
                ];
            })());

        $expected = [
            'uuid' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@example.com',
        ];

        $this->assertEquals($expected, $userView->jsonSerialize());
    }
}
