<?php

namespace App\Libraries\FluxCapacitor\EventStore\Traits;

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;

trait AggregateTrackerTrait
{
    /** @var array<string, AggregateRootInterface> */
    private array $trackedAggregates = [];

    public function getTrackedAggregates(): array
    {
        return array_values($this->trackedAggregates);
    }

    public function clearTrackedAggregates(): void
    {
        $this->trackedAggregates = [];
    }

    public function trackAggregate(AggregateRootInterface $aggregate): void
    {
        $this->trackedAggregates[$aggregate->getAggregateId()] = $aggregate;
    }

    public function trackAggregates(array $aggregates): void
    {
        foreach ($aggregates as $aggregate) {
            $this->trackAggregate($aggregate);
        }
    }

    private function untrackAggregate(AggregateRootInterface $aggregate): void
    {
        unset($this->trackedAggregates[$aggregate->getAggregateId()]);
    }
}
