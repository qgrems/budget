<?php

declare(strict_types=1);

namespace App\Tests\UserContext\Domain\Services;

use App\UserContext\Domain\Services\PasswordResetTokenGenerator;
use PHPUnit\Framework\TestCase;

class PasswordResetTokenGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $generator = new PasswordResetTokenGenerator();
        $token = $generator->generate();

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token));
    }
}
