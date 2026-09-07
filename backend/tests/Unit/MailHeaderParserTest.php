<?php

namespace Tests\Unit;

use App\Services\Inbox\MailHeaderParser;
use Tests\TestCase;

class MailHeaderParserTest extends TestCase
{
    public function test_it_parses_from_headers(): void
    {
        $parser = new MailHeaderParser;

        $this->assertSame(
            ['name' => 'Shop', 'email' => 'deals@shop.com'],
            $parser->parseFrom('Shop <deals@shop.com>')
        );

        $this->assertSame(
            ['name' => 'Ada Lovelace', 'email' => 'ada@example.com'],
            $parser->parseFrom('"Ada Lovelace" <ada@example.com>')
        );

        $this->assertSame(
            ['name' => null, 'email' => 'solo@example.com'],
            $parser->parseFrom('solo@example.com')
        );
    }

    public function test_it_reads_headers_case_insensitively(): void
    {
        $parser = new MailHeaderParser;
        $headers = [
            ['name' => 'List-Unsubscribe', 'value' => '<https://shop.com/unsub>'],
        ];

        $this->assertSame('<https://shop.com/unsub>', $parser->value($headers, 'list-unsubscribe'));
    }
}
