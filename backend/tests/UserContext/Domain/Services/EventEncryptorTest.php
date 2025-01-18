<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Services;

use App\UserContext\Domain\Attributes\PersonalData;
use App\UserContext\Domain\Events\UserFirstnameUpdatedDomainEvent;
use App\UserContext\Domain\Ports\Inbound\EncryptionServiceInterface;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;
use App\UserContext\Domain\Services\EventEncryptor;
use PHPUnit\Framework\TestCase;

final class EventEncryptorTest extends TestCase
{
    private EncryptionServiceInterface $encryptionService;
    private EventEncryptor $eventEncryptor;

    protected function setUp(): void
    {
        $this->encryptionService = $this->createMock(EncryptionServiceInterface::class);
        $this->eventEncryptor = new EventEncryptor($this->encryptionService);
    }

    public function testEncrypt(): void
    {
        $userId = 'user123';
        $event = new UserFirstnameUpdatedDomainEvent('aggregateId', 'sensitive data');

        $this->encryptionService
            ->method('encrypt')
            ->with('sensitive data', $userId, false)
            ->willReturn([
                'ciphertext' => 'encryptedData',
                'iv' => 'iv',
                'tag' => 'tag',
            ]);

        $encryptedEvent = $this->eventEncryptor->encrypt($event, $userId);

        $this->assertSame(
            json_encode(['ciphertext' => 'encryptedData', 'iv' => 'iv', 'tag' => 'tag']),
            $encryptedEvent->firstname
        );
    }

    public function testDecrypt(): void
    {
        $userId = 'user123';
        $event = new UserFirstnameUpdatedDomainEvent('aggregateId', json_encode([
            'ciphertext' => 'encryptedData',
            'iv' => 'iv',
            'tag' => 'tag',
        ]));

        $this->encryptionService
            ->method('decrypt')
            ->with('encryptedData', 'iv', 'tag', $userId)
            ->willReturn('sensitive data');

        $decryptedEvent = $this->eventEncryptor->decrypt($event, $userId);

        $this->assertSame('sensitive data', $decryptedEvent->firstname);
    }
}
