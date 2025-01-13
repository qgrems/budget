<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Events;

use App\UserContext\Domain\Events\UserSignedUpEvent;
use PHPUnit\Framework\TestCase;

class UserSignedUpEventTest extends TestCase
{
    public function testToArray(): void
    {
        $event = new UserSignedUpEvent(
            'b7e685be-db83-4866-9f85-102fac30a50b',
            'test@example.com',
            'password123',
            'John',
            'Doe',
            true,
            ['ROLE_USER']
        );
        $array = $event->toArray();

        $this->assertEquals('b7e685be-db83-4866-9f85-102fac30a50b', $array['aggregateId']);
        $this->assertEquals('test@example.com', $array['email']);
        $this->assertEquals('password123', $array['password']);
        $this->assertEquals('John', $array['firstname']);
        $this->assertEquals('Doe', $array['lastname']);
        $this->assertTrue($array['isConsentGiven']);
        $this->assertEquals(['ROLE_USER'], $array['roles']);
        $this->assertEquals($event->occurredOn->format(\DateTimeInterface::ATOM), $array['occurredOn']);
    }

    public function testFromArray(): void
    {
        $data = [
            'aggregateId' => 'b7e685be-db83-4866-9f85-102fac30a50b',
            'email' => 'test@example.com',
            'password' => 'password123',
            'firstname' => 'John',
            'lastname' => 'Doe',
            'isConsentGiven' => true,
            'roles' => ['ROLE_USER'],
            'occurredOn' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        $event = UserSignedUpEvent::fromArray($data);

        $this->assertEquals($data['aggregateId'], $event->aggregateId);
        $this->assertEquals($data['email'], $event->email);
        $this->assertEquals($data['password'], $event->password);
        $this->assertEquals($data['firstname'], $event->firstname);
        $this->assertEquals($data['lastname'], $event->lastname);
        $this->assertTrue($event->isConsentGiven);
        $this->assertEquals($data['roles'], $event->roles);
        $this->assertEquals($data['occurredOn'], $event->occurredOn->format(\DateTimeInterface::ATOM));
    }
}
