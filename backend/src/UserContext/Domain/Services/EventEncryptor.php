<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Services;

use App\UserContext\Domain\Attributes\PersonalData;
use App\UserContext\Domain\Ports\Inbound\EncryptionServiceInterface;
use App\UserContext\Domain\Ports\Inbound\EventEncryptorInterface;
use App\UserContext\Domain\Ports\Inbound\UserDomainEventInterface;
use App\UserContext\Domain\Ports\Inbound\UserSignedUpDomainEventInterface;
use ReflectionClass;
use ReflectionProperty;

final class EventEncryptor implements EventEncryptorInterface
{
    public function __construct(private readonly EncryptionServiceInterface $encryptionService)
    {
    }

    public function encrypt(UserDomainEventInterface $event, string $userId): UserDomainEventInterface
    {
        $reflection = new ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasPersonalDataAttribute($property)) {
                $property->setAccessible(true);
                $value = $property->getValue($event);

                if (!is_null($value)) {
                    $encrypted = $this->encryptionService->encrypt(
                        (string) $value,
                        $userId,
                        $event instanceof UserSignedUpDomainEventInterface,
                    );
                    $property->setValue($event, json_encode($encrypted));
                }
            }
        }

        return $event;
    }

    public function decrypt(UserDomainEventInterface $event, string $userId): UserDomainEventInterface
    {
        $reflection = new ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasPersonalDataAttribute($property)) {
                $property->setAccessible(true);
                $value = $property->getValue($event);

                if (!is_null($value)) {
                    $encrypted = json_decode($value, true);
                    $decrypted = $this->encryptionService->decrypt(
                        $encrypted['ciphertext'],
                        $encrypted['iv'],
                        $encrypted['tag'],
                        $userId,
                    );
                    $property->setValue($event, $decrypted);
                }
            }
        }

        return $event;
    }

    private function hasPersonalDataAttribute(ReflectionProperty $property): bool
    {
        return count($property->getAttributes(PersonalData::class)) > 0;
    }
}
