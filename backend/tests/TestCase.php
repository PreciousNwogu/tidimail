<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Services\Gmail\FakeGmailGateway;
use App\Services\Gmail\GmailGateway;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected FakeGmailGateway $gmail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gmail = new FakeGmailGateway;
        $this->app->instance(GmailGateway::class, $this->gmail);
    }
}
