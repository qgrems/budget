<?php

declare(strict_types=1);

namespace App\Tests;

class CreateEventGenerator
{
    public static function create(array $events): \Generator
    {
        foreach ($events as $event) {
            yield $event;
        }
    }
}
