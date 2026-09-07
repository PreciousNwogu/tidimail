<?php

namespace Tests\Unit;

use App\Services\Gmail\GmailException;
use RuntimeException;
use Tests\TestCase;

class GmailExceptionTest extends TestCase
{
    public function test_sanitize_hides_raw_google_json(): void
    {
        $raw = new RuntimeException('{"error":{"code":401,"message":"Invalid Credentials","status":"UNAUTHENTICATED"}}');

        $this->assertSame(
            'Gmail access expired. Reconnect Google to continue.',
            GmailException::sanitize($raw)->getMessage()
        );
    }

    public function test_sanitize_maps_rate_limits(): void
    {
        $raw = new RuntimeException('Client error: 429 Too Many Requests {"error":{"code":429}}');

        $this->assertSame(
            'Gmail asked us to slow down. Wait a minute, then try the scan again.',
            GmailException::sanitize($raw)->getMessage()
        );
    }
}
