<?php

declare(strict_types=1);

namespace App\Libraries\FluxCapacitor\Services;

use App\Libraries\FluxCapacitor\Ports\EventClassMapInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Yaml;

final readonly class EventClassMap implements EventClassMapInterface
{
    private array $eventMappings;
    private array $aggregateMappings;

    public function __construct(KernelInterface $kernel)
    {
        $config = Yaml::parseFile($kernel->getProjectDir().'/config/fluxCapacitor/event_mappings.yaml');
        $this->eventMappings = $config['events'] ?? [];
        $this->aggregateMappings = $config['aggregates'] ?? [];
    }

    public function getClassNameByEventPath(string $eventPath): string
    {
        return array_search($eventPath, $this->eventMappings, true) ?: $eventPath;
    }

    public function getEventPathByClassName(string $eventClassName): string
    {
        return $this->eventMappings[$eventClassName] ?? $eventClassName;
    }

    public function getStreamNameByEventPath(string $eventPath): string
    {
        foreach ($this->aggregateMappings as $streamName => $aggregateClass) {
            if (str_contains($eventPath, $streamName)) {
                return $streamName;
            }
        }
        return $eventPath;
    }

    public function getAggregatePathByByStreamName(string $streamName): string
    {
        return $this->aggregateMappings[$streamName] ?? $streamName;
    }

    public function getClassNamesByEventsPaths(array $eventsPaths): array
    {
        return array_map([$this, 'getClassNameByEventPath'], $eventsPaths);
    }
}
