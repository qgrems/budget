<?php

namespace App\Libraries\FluxCapacitor\Ports;

interface EventClassMapInterface
{
    public function getClassNameByEventPath(string $eventPath): string;
    public function getClassNamesByEventsPaths(array $eventsPaths): array;
}
