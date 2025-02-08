<?php

namespace App\UserContext\Domain\Ports\Inbound;

use App\SharedContext\Domain\Ports\Inbound\EventClassMapInterface as SharedEventClassMapInterface;

interface EventClassMapInterface extends SharedEventClassMapInterface
{
    public function getClassNameByEventPath(string $eventPath): string;

    public function getEventPathByClassName(string $eventClassName): string;

    public function getClassNamesByEventsPaths(array $eventsPaths): array;
}
