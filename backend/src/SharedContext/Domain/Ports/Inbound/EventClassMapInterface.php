<?php

namespace App\SharedContext\Domain\Ports\Inbound;

interface EventClassMapInterface
{
    public function getClassNameByEventPath(string $eventPath): string;

    public function getEventPathByClassName(string $eventClassName): string;

    public function getClassNamesByEventsPaths(array $eventsPaths): array;
}
