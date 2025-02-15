<?php

declare(strict_types=1);

namespace App\Libraries\Anonymii\Traits;

trait EncryptedKeyCacheTrait
{
    private array $keys = [];

    protected function storeKeyByUserId(string $userId, string $key): void
    {
        $this->keys[$userId] = $key;
    }

    public function getKeyByUserId(string $userId): ?string
    {
        return $this->keys[$userId] ?? null;
    }

    public function clearKeys(): array
    {
        return $this->keys = [];
    }
}
