<?php
/**
 * Senior note:
 * - Lightweight helper sanity check keeps CI green and fast.
 */

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Str;

class StrHelpersTest extends TestCase
{
    public function test_uuid_is_valid_format()
    {
        $uuid = (string) Str::uuid();
        $this->assertMatchesRegularExpression('/^[a-f0-9\-]{36}$/', $uuid);
    }
}
