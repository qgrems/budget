<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Ports\Inbound;

interface KeyManagementRepositoryInterface
{
    public function generateKey(string $userId): string;

    public function getKey(string $userId): ?string;

    public function deleteKey(string $userId): void;
}
