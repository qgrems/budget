<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Services;

use App\Libraries\Anonymii\Exceptions\UserEncryptionException;
use App\Libraries\Anonymii\Repositories\KeyManagementRepositoryInterface;
use App\Libraries\Anonymii\Services\EncryptionService;
use App\Libraries\Anonymii\Services\EncryptionServiceInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EncryptionServiceTest extends TestCase
{
    private KeyManagementRepositoryInterface $keyManagementRepository;
    private EncryptionServiceInterface $encryptionService;

    protected function setUp(): void
    {
        $this->keyManagementRepository = $this->createMock(KeyManagementRepositoryInterface::class);
        $this->encryptionService = new EncryptionService($this->keyManagementRepository);
    }

    public function testEncrypt(): void
    {
        $userId = 'user123';
        $data = 'sensitive data';
        $key = 'encryptionkey';

        $this->keyManagementRepository
            ->method('getKey')
            ->with($userId)
            ->willReturn($key);

        $encryptedData = $this->encryptionService->encrypt($data, $userId);

        $this->assertArrayHasKey('ciphertext', $encryptedData);
        $this->assertArrayHasKey('iv', $encryptedData);
        $this->assertArrayHasKey('tag', $encryptedData);
    }

    public function testDecrypt(): void
    {
        $userId = 'user123';
        $data = 'sensitive data';
        $key = 'encryptionkey';

        $this->keyManagementRepository
            ->method('getKey')
            ->with($userId)
            ->willReturn($key);

        $encryptedData = $this->encryptionService->encrypt($data, $userId);
        $decryptedData = $this->encryptionService->decrypt(
            $encryptedData['ciphertext'],
            $encryptedData['iv'],
            $encryptedData['tag'],
            $userId
        );

        $this->assertSame($data, $decryptedData);
    }

    public function testEncryptThrowsExceptionOnFailure(): void
    {
        $this->expectException(UserEncryptionException::class);

        $userId = 'user123';
        $data = 'sensitive data';

        $this->keyManagementRepository
            ->method('getKey')
            ->with($userId)
            ->willReturn(null);

        $this->encryptionService->encrypt($data, $userId);
    }

    public function testDecryptThrowsExceptionOnFailure(): void
    {
        $this->expectException(UserEncryptionException::class);

        $userId = 'user123';
        $ciphertext = 'invalidciphertext';
        $iv = 'invalidiv';
        $tag = 'invalidtag';

        $this->keyManagementRepository
            ->method('getKey')
            ->with($userId)
            ->willReturn('encryptionkey');

        $this->encryptionService->decrypt($ciphertext, $iv, $tag, $userId);
    }

    public function testGetKeyForUserGeneratesAndStoresKeyOnSignUp(): void
    {
        $userId = 'user123';
        $generatedKey = 'generatedkey';

        $this->keyManagementRepository
            ->method('getKey')
            ->with($userId)
            ->willReturn(null);

        $this->keyManagementRepository
            ->method('generateKey')
            ->with($userId)
            ->willReturn($generatedKey);

        $reflection = new ReflectionClass($this->encryptionService);
        $method = $reflection->getMethod('getKeyForUser');
        $method->setAccessible(true);

        $key = $method->invokeArgs($this->encryptionService, [$userId, true]);

        $this->assertSame($generatedKey, $key);
    }
}
