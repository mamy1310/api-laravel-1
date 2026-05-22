<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function test_professional_email(): void
    {
        $user = new User();
        $user->email = 'john@entreprise.com';

        $this->assertTrue($user->usesProfessionalEmail());
    }

    public function test_non_professional_email(): void
    {
        $user = new User();
        $user->email = 'john@gmail.com';

        $this->assertFalse($user->usesProfessionalEmail());
    }
}
