<?php

declare(strict_types=1);

namespace App\Tests\UserManagement\Domain\Services;

use App\UserManagement\Domain\Services\PasswordResetTokenGenerator;
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
