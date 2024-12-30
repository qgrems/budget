<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\ReadModels\Views;

use App\UserManagement\ReadModels\Views\UserView;
use PHPUnit\Framework\TestCase;

class UserViewTest extends TestCase
{
    public function testUserView(): void
    {
        $userView = new UserView();
        $userView->setUuid('b7e685be-db83-4866-9f85-102fac30a50b')
            ->setEmail('john.doe@example.com')
            ->setPassword('password123')
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setConsentGiven(true)
            ->setConsentDate(new \DateTimeImmutable('2023-01-01T00:00:00+00:00'))
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN'])
            ->setPasswordResetToken('reset-token-123')
            ->setPasswordResetTokenExpiry(new \DateTimeImmutable('+1 hour'))
            ->setCreatedAt(new \DateTimeImmutable('2023-01-01T00:00:00+00:00'))
            ->setUpdatedAt(new \DateTime('2023-01-01T00:00:00+00:00'));

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $userView->getUuid());
        $this->assertEquals('john.doe@example.com', $userView->getEmail());
        $this->assertEquals('password123', $userView->getPassword());
        $this->assertEquals('John', $userView->getFirstname());
        $this->assertEquals('Doe', $userView->getLastname());
        $this->assertTrue($userView->isConsentGiven());
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->getConsentDate()->format(\DateTimeInterface::ATOM));
        $this->assertEquals(['ROLE_USER', 'ROLE_ADMIN'], $userView->getRoles());
        $this->assertEquals('reset-token-123', $userView->getPasswordResetToken());
        $this->assertEquals((new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM), $userView->getPasswordResetTokenExpiry()->format(\DateTimeInterface::ATOM));
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->getCreatedAt()->format(\DateTimeInterface::ATOM));
        $this->assertEquals('2023-01-01T00:00:00+00:00', $userView->getUpdatedAt()->format(\DateTimeInterface::ATOM));
    }

    public function testSetAndGetUuid(): void
    {
        $userView = new UserView();
        $userView->setUuid('b7e685be-db83-4866-9f85-102fac30a50b');
        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $userView->getUuid());
    }

    public function testSetAndGetEmail(): void
    {
        $userView = new UserView();
        $userView->setEmail('john.doe@example.com');
        $this->assertEquals('john.doe@example.com', $userView->getEmail());
    }

    public function testSetAndGetPassword(): void
    {
        $userView = new UserView();
        $userView->setPassword('password123');
        $this->assertEquals('password123', $userView->getPassword());
    }

    public function testSetAndGetFirstname(): void
    {
        $userView = new UserView();
        $userView->setFirstname('John');
        $this->assertEquals('John', $userView->getFirstname());
    }

    public function testSetAndGetLastname(): void
    {
        $userView = new UserView();
        $userView->setLastname('Doe');
        $this->assertEquals('Doe', $userView->getLastname());
    }

    public function testSetAndGetConsentGiven(): void
    {
        $userView = new UserView();
        $userView->setConsentGiven(true);
        $this->assertTrue($userView->isConsentGiven());
    }

    public function testSetAndGetConsentDate(): void
    {
        $userView = new UserView();
        $consentDate = new \DateTimeImmutable('2023-01-01T00:00:00+00:00');
        $userView->setConsentDate($consentDate);
        $this->assertEquals($consentDate, $userView->getConsentDate());
    }

    public function testSetAndGetRoles(): void
    {
        $userView = new UserView();
        $roles = ['ROLE_USER', 'ROLE_ADMIN'];
        $userView->setRoles($roles);
        $this->assertEquals($roles, $userView->getRoles());
    }

    public function testSetAndGetPasswordResetToken(): void
    {
        $userView = new UserView();
        $userView->setPasswordResetToken('reset-token-123');
        $this->assertEquals('reset-token-123', $userView->getPasswordResetToken());
    }

    public function testSetAndGetPasswordResetTokenExpiry(): void
    {
        $userView = new UserView();
        $expiryDate = new \DateTimeImmutable('+1 hour');
        $userView->setPasswordResetTokenExpiry($expiryDate);
        $this->assertEquals($expiryDate, $userView->getPasswordResetTokenExpiry());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $userView = new UserView();
        $createdAt = new \DateTimeImmutable('2023-01-01T00:00:00+00:00');
        $userView->setCreatedAt($createdAt);
        $this->assertEquals($createdAt, $userView->getCreatedAt());
    }

    public function testSetAndGetUpdatedAt(): void
    {
        $userView = new UserView();
        $updatedAt = new \DateTime('2023-01-01T00:00:00+00:00');
        $userView->setUpdatedAt($updatedAt);
        $this->assertEquals($updatedAt, $userView->getUpdatedAt());
    }

    public function testEraseCredentials(): void
    {
        $userView = new UserView();
        $userView->eraseCredentials();
        $this->assertTrue(true); // No exception should be thrown
    }

    public function testGetUserIdentifier(): void
    {
        $userView = new UserView();
        $userView->setEmail('john.doe@example.com');
        $this->assertEquals('john.doe@example.com', $userView->getUserIdentifier());
    }

    public function testSetAndGetId(): void
    {
        $userView = new UserView();
        $userView->setId(1);
        $this->assertEquals(1, $userView->getId());
    }

    public function testJsonSerialize(): void
    {
        $userView = new UserView();
        $userView->setUuid('b7e685be-db83-4866-9f85-102fac30a50b')
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('john.doe@example.com');

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
            'roles' => json_encode(['ROLE_USER', 'ROLE_ADMIN']),
            'password_reset_token' => 'reset-token-123',
            'password_reset_token_expiry' => (new \DateTimeImmutable('+1 hour'))->format(\DateTimeInterface::ATOM),
        ];

        $userView = UserView::createFromRepository($userData);

        $this->assertEquals($userData['id'], $userView->getId());
        $this->assertEquals($userData['uuid'], $userView->getUuid());
        $this->assertEquals($userData['email'], $userView->getEmail());
        $this->assertEquals($userData['password'], $userView->getPassword());
        $this->assertEquals($userData['firstname'], $userView->getFirstname());
        $this->assertEquals($userData['lastname'], $userView->getLastname());
        $this->assertEquals($userData['consent_given'], $userView->isConsentGiven());
        $this->assertEquals($userData['consent_date'], $userView->getConsentDate()->format(\DateTimeInterface::ATOM));
        $this->assertEquals($userData['created_at'], $userView->getCreatedAt()->format(\DateTimeInterface::ATOM));
        $this->assertEquals($userData['updated_at'], $userView->getUpdatedAt()->format(\DateTimeInterface::ATOM));
        $this->assertEquals(json_decode($userData['roles'], true), $userView->getRoles());
        $this->assertEquals($userData['password_reset_token'], $userView->getPasswordResetToken());
        $this->assertEquals($userData['password_reset_token_expiry'], $userView->getPasswordResetTokenExpiry()->format(\DateTimeInterface::ATOM));
    }
}
