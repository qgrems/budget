<?php

declare(strict_types=1);

namespace App\UserContext\Domain\Attributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class PersonalData
{
    public function __construct()
    {
    }
}
