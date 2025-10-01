<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\LifeboxUsernameRule;
use Tests\TestCase;

class LifeboxUsernameRuleTest extends TestCase
{
    private $usernameRule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usernameRule = new LifeboxUsernameRule();
    }

    /**
     * @dataProvider validUsernames
     */
    public function testValidUsernames(string $username)
    {
        $this->assertTrue($this->usernameRule->passes('username', $username));
    }

    /**
     * @dataProvider invalidUsernames
     */
    public function testInvalidUsernames(string $username)
    {
        $this->assertFalse($this->usernameRule->passes('username', $username));
    }

    public function validUsernames(): array
    {
        return [
            ['test'],
            ['foo+'],
            ['xyz'],
        ];
    }

    public function invalidUsernames(): array
    {
        return [
            ['test.'],
            ['foo@'],
            ['@foo.'],
        ];
    }
}
