<?php

declare(strict_types=1);

namespace App\Libraries\Anonymii\Services;

use App\Libraries\Anonymii\Attributes\PersonalData;
use App\Libraries\Anonymii\Events\AnonymiiUserDomainEventInterface;
use App\Libraries\Anonymii\Events\AnonymiiUserSignedUpDomainEventInterface;
use ReflectionClass;
use ReflectionProperty;

final class EventEncryptor implements EventEncryptorInterface
{
    public function __construct(private readonly EncryptionServiceInterface $encryptionService)
    {
    }

    public function encrypt(AnonymiiUserDomainEventInterface $event, string $userId): AnonymiiUserDomainEventInterface
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
                        $event instanceof AnonymiiUserSignedUpDomainEventInterface,
                    );
                    $property->setValue($event, json_encode($encrypted));
                }
            }
        }

        return $event;
    }

    public function decrypt(AnonymiiUserDomainEventInterface $event, string $userId): AnonymiiUserDomainEventInterface
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
