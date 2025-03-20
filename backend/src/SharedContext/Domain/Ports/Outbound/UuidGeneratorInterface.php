<?php

declare(strict_types=1);

namespace App\SharedContext\Domain\Ports\Outbound;

interface UuidGeneratorInterface
{
    public function generate(): string;

    public static function uuidV5(string $namespace, string $name): string;
}
