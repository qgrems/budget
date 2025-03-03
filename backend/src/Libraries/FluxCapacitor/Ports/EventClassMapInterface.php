<?php

namespace App\Libraries\FluxCapacitor\Ports;

interface EventClassMapInterface
{
    public function getClassNameByEventPath(string $eventPath): string;

    public function getEventPathByClassName(string $eventClassName): string;

    public function getClassNamesByEventsPaths(array $eventsPaths): array;

    public function getAggregatePathByByStreamName(string $streamName): string;

    public function getStreamNameByEventPath(string $eventPath): string;
}
